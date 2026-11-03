<?php

namespace Tests\Feature;

use App\Models\Exchange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class OrganizerLoginTest extends TestCase
{
    use RefreshDatabase;

    private function code(string $email = 'owner@example.test'): string
    {
        $this->post('/login/code', ['email' => $email])->assertRedirect('/login')->assertSessionDoesntHaveErrors();
        $html = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage()->getHtmlBody();
        preg_match('/>([0-9]{6})</', $html, $matches);

        return $matches[1];
    }

    private function event(string $email = 'owner@example.test'): Exchange
    {
        $token = Str::random(64);

        return Exchange::create(['uuid' => (string) Str::uuid(), 'name' => 'Team', 'organizer_email' => $email, 'access_hash' => hash('sha256', $token), 'access_token' => $token, 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20]);
    }

    public function test_code_login_lists_only_owned_events_and_never_exposes_tokens(): void
    {
        $first = $this->event();
        $second = $this->event();
        $other = $this->event('other@example.test');
        $code = $this->code('Owner@example.test');
        $row = DB::table('login_codes')->first();
        $this->assertNotSame($code, $row->code_hash);
        $this->assertDatabaseCount('jobs', 0);
        $this->post('/login/verify', ['code' => $code])->assertRedirect('/my-events')->assertSessionHas('organizer_email', 'owner@example.test');
        $this->assertDatabaseCount('login_codes', 0);
        $this->get('/my-events')->assertInertia(fn (Assert $p) => $p->has('events', 2)->missing('events.0.access_token')->missing('events.0.organizer_email'));
        foreach ([$first, $second] as $event) {
            $this->get(route('exchanges.manage', $event))->assertOk();
        }
        $this->get(route('exchanges.manage', $other))->assertForbidden();
        $this->post('/login/verify', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_legacy_link_and_session_cannot_authorize_without_email_verification(): void
    {
        $event = $this->event();
        $this->withSession(["managed.{$event->id}" => 1]);
        $this->get(route('access.show', $event->access_token))->assertRedirect('/login');
        $this->post(route('access.activate', $event->access_token))->assertRedirect('/login')->assertSessionMissing('organizer_email');
        $this->get(route('exchanges.manage', $event))->assertForbidden();
        $this->post(route('exchanges.cancel', $event), ['confirm' => 1])->assertForbidden();
        $this->withSession(['organizer_email' => 'attacker@example.test'])->get(route('exchanges.manage', $event))->assertForbidden();
    }

    public function test_creation_requires_verified_address_and_ignores_injected_owner(): void
    {
        Queue::fake();
        $data = ['name' => 'Team', 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20, 'organizer_email' => 'victim@example.test'];
        $this->get('/create')->assertRedirect('/login');
        $this->get('/my-events')->assertRedirect('/login');
        $this->post('/events', $data)->assertForbidden();
        $this->assertDatabaseCount('exchanges', 0);
        $code = $this->code();
        $this->post('/login/verify', ['code' => $code]);
        $this->post('/events', $data)->assertSessionDoesntHaveErrors();
        $this->assertSame('owner@example.test', Exchange::first()->organizer_email);
    }

    public function test_code_expires_is_bound_to_requesting_session_and_allows_only_five_attempts(): void
    {
        $code = $this->code();
        $pending = session('login');
        $this->flushSession();
        $this->post('/login/verify', ['code' => $code])->assertSessionHasErrors('code')->assertSessionMissing('organizer_email');
        $this->withSession(['login' => $pending]);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        }
        $this->assertSame(5, DB::table('login_codes')->value('attempts'));
        $this->post('/login/verify', ['code' => $code])->assertSessionHasErrors('code')->assertSessionMissing('organizer_email');
        $this->travel(61)->seconds();
        $code = $this->code();
        $this->travel(10)->minutes();
        $this->post('/login/verify', ['code' => $code])->assertSessionHasErrors('code')->assertSessionMissing('organizer_email');
    }

    public function test_resend_invalidates_previous_challenge_and_enforces_cooldown(): void
    {
        $old = $this->code();
        $pending = session('login');
        $this->post('/login/code', ['email' => 'owner@example.test'])->assertSessionHasErrors('email');
        $this->travel(61)->seconds();
        $new = $this->code();
        $latest = session('login');
        $this->withSession(['login' => $pending])->post('/login/verify', ['code' => $old])->assertSessionHasErrors('code');
        $this->withSession(['login' => $latest])->post('/login/verify', ['code' => $new])->assertRedirect('/my-events');
    }

    public function test_delivery_failure_rolls_back_and_does_not_leak_transport_details(): void
    {
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('smtp secret@example.test'));
        $this->post('/login/code', ['email' => 'owner@example.test'])->assertSessionHasErrors('email')->assertSessionMissing('login');
        $this->assertDatabaseCount('login_codes', 0);
        $this->assertStringNotContainsString('secret@example.test', session('errors')->first('email'));
    }

    public function test_email_rate_limit_and_invalid_input_do_not_issue_more_codes(): void
    {
        $this->post('/login/code', ['email' => ['bad']])->assertSessionHasErrors('email');
        for ($i = 0; $i < 5; $i++) {
            $this->code();
            $this->travel(61)->seconds();
        }
        $this->post('/login/code', ['email' => 'OWNER@example.test'])->assertStatus(429);
        $this->assertCount(5, Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_login_migration_rolls_back_without_changing_existing_events(): void
    {
        $event = $this->event();
        $migration = require database_path('migrations/2026_11_03_000004_create_login_codes.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('login_codes'));
        $this->assertSame($event->uuid, Exchange::first()->uuid);
        $migration->up();
        $code = $this->code();
        $this->post('/login/verify', ['code' => $code])->assertRedirect('/my-events');
    }

    public function test_logout_removes_organizer_and_pending_login_access(): void
    {
        $event = $this->event();
        $code = $this->code();
        $this->post('/login/verify', ['code' => $code]);
        $this->post('/logout')->assertSessionMissing('organizer_email')->assertSessionMissing('managed')->assertSessionMissing('login');
        $this->get(route('exchanges.manage', $event))->assertForbidden();
    }
}
