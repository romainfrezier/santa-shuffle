<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Jobs\SendAccessLink;
use App\Models\Delivery;
use App\Models\Exchange;
use App\Services\DrawNames;
use App\ViewModels\ExchangeView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    private Exchange $event;

    private $people;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $token = Str::random(64);
        $this->event = Exchange::create(['uuid' => (string) Str::uuid(), 'name' => 'Studio', 'organizer_email' => 'owner@example.test', 'access_hash' => hash('sha256', $token), 'access_token' => $token, 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20]);
        $this->people = collect(range(1, 3))->map(function ($n) {
            $token = Str::random(64);

            return $this->event->participants()->create(['name' => "Person $n", 'email' => "person$n@example.test", 'access_token' => $token, 'access_hash' => hash('sha256', $token), 'confirmed_at' => now()]);
        });
    }

    private function manager(): static
    {
        return $this->withSession(['organizer_email' => 'owner@example.test', "managed.{$this->event->id}" => 1]);
    }

    private function draw(): void
    {
        $this->manager()->post(route('exchanges.draw', $this->event), ['confirm' => 1])->assertSessionDoesntHaveErrors();
    }

    public function test_currency_is_validated_persisted_and_locked_after_drawing(): void
    {
        $this->assertSame('EUR', $this->event->fresh()->currency);
        $data = ['name' => 'Studio', 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 25];
        $this->manager();
        foreach (array_keys(Exchange::CURRENCIES) as $currency) {
            $this->patch(route('exchanges.update', $this->event), [...$data, 'currency' => $currency])->assertSessionDoesntHaveErrors();
            $this->assertSame($currency, ExchangeView::summary($this->event->fresh())['currency']);
        }
        foreach (['XXX', 'eur', '<script>', '', ['EUR']] as $currency) {
            $this->patch(route('exchanges.update', $this->event), [...$data, 'currency' => $currency])->assertSessionHasErrors('currency');
            $this->assertSame('AUD', $this->event->fresh()->currency);
        }
        $this->patch(route('exchanges.update', $this->event), $data)->assertSessionDoesntHaveErrors();
        $this->assertSame('AUD', $this->event->fresh()->currency);
        $this->draw();
        $this->patch(route('exchanges.update', $this->event), [...$data, 'currency' => 'CHF'])->assertConflict();
        $this->assertSame('AUD', $this->event->fresh()->currency);
    }

    public function test_create_currency_defaults_and_rejects_unknown_codes(): void
    {
        $data = ['name' => 'Gift', 'organizer_email' => 'new@example.test', 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20];
        $this->withSession(['organizer_email' => 'new@example.test'])->post('/events', [...$data, 'currency' => 'INVALID'])->assertSessionHasErrors('currency');
        $this->assertDatabaseCount('exchanges', 1);
        $this->withSession(['organizer_email' => 'new@example.test'])->post('/events', $data)->assertSessionDoesntHaveErrors();
        $this->assertSame('EUR', Exchange::latest('id')->first()->currency);
        $this->withSession(['organizer_email' => 'new@example.test'])->post('/events', [...$data, 'currency' => 'JPY'])->assertSessionDoesntHaveErrors();
        $this->assertSame('JPY', Exchange::latest('id')->first()->currency);
    }

    public function test_reopen_invite_confirm_and_redraw_preserves_exclusions(): void
    {
        DB::table('exclusions')->insert(['exchange_id' => $this->event->id, 'giver_id' => $this->people[0]->id, 'recipient_id' => $this->people[1]->id]);
        $this->draw();
        $old = Delivery::where('kind', 'draw')->first();
        $this->post(route('exchanges.reopen', $this->event), ['confirm' => 1])->assertSessionDoesntHaveErrors();
        $this->assertNull($this->event->fresh()->drawn_at);
        $this->assertSame(0, $this->event->participants()->whereNotNull('recipient_id')->count());
        $this->assertDatabaseCount('exclusions', 1);
        (new SendAccessLink($old->id))->handle();
        $this->assertSame('obsolete', $old->fresh()->status);
        $this->post(route('exchanges.invite', $this->event), ['name' => 'Late', 'email' => 'late@example.test'])->assertSessionDoesntHaveErrors();
        $this->post(route('exchanges.draw', $this->event), ['confirm' => 1])->assertSessionHasErrors('draw');
        $late = $this->event->participants()->where('email', 'late@example.test')->firstOrFail();
        $this->post(route('access.activate', $late->access_token));
        $this->post(route('exchanges.draw', $this->event), ['confirm' => 1])->assertSessionDoesntHaveErrors();
        $this->assertSame(4, $this->event->participants()->whereNotNull('recipient_id')->count());
        $this->assertSame(4, $this->event->participants()->distinct()->count('recipient_id'));
        $this->assertNotSame($this->people[1]->id, $this->people[0]->fresh()->recipient_id);
    }

    public function test_reveal_permanently_blocks_reopening_and_reopen_blocks_reveal(): void
    {
        $this->draw();
        $this->post(route('access.activate', $this->people[0]->access_token));
        $this->post(route('participants.reveal', $this->event))->assertRedirect();
        $pairs = $this->event->participants()->pluck('recipient_id')->all();
        $this->post(route('exchanges.reopen', $this->event), ['confirm' => 1])->assertSessionHasErrors('reopen');
        $this->assertSame($pairs, $this->event->participants()->pluck('recipient_id')->all());
    }

    public function test_reopened_event_cannot_be_revealed(): void
    {
        $this->draw();
        $this->post(route('exchanges.reopen', $this->event), ['confirm' => 1]);
        $this->post(route('access.activate', $this->people[0]->access_token));
        $this->post(route('participants.reveal', $this->event))->assertConflict();
    }

    public function test_every_management_action_rejects_anonymous_participants_and_other_managers(): void
    {
        $urls = [['patch', 'exchanges.update'], ['delete', 'exchanges.destroy'], ['post', 'exchanges.invite'], ['post', 'exchanges.reopen'], ['post', 'exchanges.cancel'], ['post', 'exchanges.rotate'], ['post', 'participants.resend'], ['post', 'participants.rotate']];
        foreach ([[], ["participants.{$this->event->id}" => ['id' => $this->people[0]->id, 'version' => 1]], ['managed.999999' => 1]] as $session) {
            $this->flushSession();
            $this->withSession($session);
            foreach ($urls as [$method, $name]) {
                $args = str_starts_with($name, 'participants.') ? [$this->event, $this->people[0]->id] : $this->event;
                $this->$method(route($name, $args), ['confirm' => 1])->assertForbidden();
            }
        }
        $this->assertNull($this->event->fresh()->cancelled_at);
    }

    public function test_manager_cannot_target_another_events_participant(): void
    {
        $this->manager();
        foreach (['participants.resend', 'participants.rotate'] as $name) {
            $this->post(route($name, [$this->event, 0]), ['confirm' => 1])->assertNotFound();
            $this->post(route($name, [$this->event, 999999]), ['confirm' => 1])->assertNotFound();
        }
    }

    public function test_rotation_revokes_old_token_and_sessions_but_preserves_confirmation_and_assignment(): void
    {
        $this->draw();
        $p = $this->people[0]->fresh();
        $recipient = $p->recipient_id;
        $this->post(route('access.activate', $p->access_token));
        $this->post(route('participants.rotate', [$this->event, $p->id]), ['confirm' => 1])->assertSessionDoesntHaveErrors();
        $this->get(route('participants.show', $this->event))->assertForbidden();
        $this->post(route('access.activate', $p->access_token))->assertNotFound();
        $this->assertNotNull($p->fresh()->confirmed_at);
        $this->assertSame($recipient, $p->fresh()->recipient_id);
        $this->post(route('access.activate', $p->fresh()->access_token))->assertRedirect();
        $this->get(route('participants.show', $this->event))->assertOk();
        $this->post(route('exchanges.rotate', $this->event), ['confirm' => 1])->assertSessionDoesntHaveErrors();
        $this->get(route('exchanges.manage', $this->event))->assertOk();
        $this->get(route('access.show', $this->event->access_token))->assertNotFound();
        $this->withSession(["managed.{$this->event->id}" => 1])->get(route('exchanges.manage', $this->event))->assertForbidden();
        $this->withSession(["managed.{$this->event->id}" => true])->get(route('exchanges.manage', $this->event))->assertForbidden();
    }

    public function test_update_allows_only_public_fields_and_draw_locks_editing(): void
    {
        $payload = ['name' => 'Nouveau', 'gift_date' => now()->addMonths(2)->toDateString(), 'budget' => 30, 'message' => 'Bonjour', 'access_version' => 99, 'organizer_email' => 'attacker@example.test', 'drawn_at' => now()->toDateTimeString()];
        $this->manager()->patch(route('exchanges.update', $this->event), $payload)->assertSessionDoesntHaveErrors();
        $this->assertSame('Nouveau', $this->event->fresh()->name);
        $this->assertSame('owner@example.test', $this->event->fresh()->organizer_email);
        $this->assertSame(1, $this->event->fresh()->access_version);
        $this->assertNull($this->event->fresh()->drawn_at);
        $this->draw();
        $this->patch(route('exchanges.update', $this->event), $payload)->assertConflict();
        $this->post(route('exchanges.cancel', $this->event), ['confirm' => 1])->assertConflict();
    }

    public function test_cancel_closes_all_participant_access_and_can_be_deleted(): void
    {
        $this->manager()->post(route('exchanges.cancel', $this->event), ['confirm' => 1])->assertSessionDoesntHaveErrors();
        $this->get(route('exchanges.show', $this->event))->assertGone();
        $this->post(route('access.activate', $this->people[0]->access_token))->assertGone();
        $this->get(route('exchanges.manage', $this->event))->assertOk();
        $this->post(route('exchanges.invite', $this->event), ['name' => 'Late', 'email' => 'late@example.test'])->assertGone();
        $this->assertSame(3, Delivery::where('kind', 'cancel')->count());
        $this->delete(route('exchanges.destroy', $this->event), ['confirm' => 1])->assertRedirect(route('home'));
        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('deliveries', 0);
    }

    public function test_destructive_actions_require_explicit_confirmation(): void
    {
        $this->manager();
        foreach (['exchanges.cancel', 'exchanges.reopen', 'exchanges.rotate'] as $name) {
            $this->post(route($name, $this->event))->assertSessionHasErrors('confirm');
        }
        $this->delete(route('exchanges.destroy', $this->event))->assertSessionHasErrors('confirm');
        $this->assertDatabaseCount('exchanges', 1);
    }

    public function test_duplicate_invitation_does_not_overwrite_or_confirm_and_limit_is_enforced(): void
    {
        $this->manager();
        $p = $this->people[0];
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('exchanges.invite', $this->event), ['name' => 'Imposteur', 'email' => strtoupper($p->email), 'confirmed_at' => now(), 'recipient_id' => $p->id])->assertSessionDoesntHaveErrors();
        }
        $this->post(route('exchanges.invite', $this->event), ['name' => 'Imposteur', 'email' => $p->email])->assertSessionHasErrors('email');
        $this->assertSame($p->name, $p->fresh()->name);
        $this->assertNull($p->fresh()->recipient_id);
        $this->post(route('participants.resend', [$this->event, $p->id]))->assertSessionHasErrors('delivery');
    }

    public function test_resend_and_rotate_share_recipient_quota(): void
    {
        $this->manager();
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('participants.resend', [$this->event, $this->people[0]->id]))->assertSessionDoesntHaveErrors();
        }
        $this->post(route('participants.rotate', [$this->event, $this->people[0]->id]), ['confirm' => 1])->assertSessionHasErrors('delivery');
        $this->travel(61)->minutes();
        $this->post(route('participants.resend', [$this->event, $this->people[0]->id]))->assertSessionDoesntHaveErrors();
    }

    public function test_delivery_is_idempotent_and_stale_links_and_deleted_events_are_not_sent(): void
    {
        $d = Delivery::enqueue($this->event, $this->people[0], 'join');
        $job = new SendAccessLink($d->id);
        $job->handle();
        $job->handle();
        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertCount(1, $transport->messages());
        $this->assertSame('accepted', $d->fresh()->status);
        $d2 = Delivery::enqueue($this->event, $this->people[0], 'join');
        $this->manager()->post(route('participants.rotate', [$this->event, $this->people[0]->id]), ['confirm' => 1])->assertSessionDoesntHaveErrors();
        (new SendAccessLink($d2->id))->handle();
        $this->assertSame('obsolete', $d2->fresh()->status);
        $last = Delivery::latest('id')->first();
        $this->delete(route('exchanges.destroy', $this->event), ['confirm' => 1]);
        (new SendAccessLink($last->id))->handle();
        $this->assertCount(1, $transport->messages());
    }

    public function test_expired_notification_is_not_sent_and_failure_status_is_safe(): void
    {
        $d = Delivery::enqueue($this->event, $this->people[0], 'join');
        $job = new SendAccessLink($d->id);
        $job->failed(new \RuntimeException('private data'));
        $this->assertSame('failed', $d->fresh()->status);
        $this->travel(3)->months();
        $job->handle();
        $this->assertSame('obsolete', $d->fresh()->status);
        $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_purge_deletes_only_expired_events_and_cascades(): void
    {
        Delivery::enqueue($this->event, $this->people[0], 'join');
        $date = $this->event->gift_date->copy()->addDays(30);
        $this->travelTo($date->copy()->subSecond());
        $this->artisan('events:purge')->assertSuccessful();
        $this->assertDatabaseCount('exchanges', 1);
        $this->travelTo($date->copy()->addSecond());
        $this->assertTrue($this->event->fresh()->gift_date->copy()->addDays(30)->isPast());
        $this->assertSame(1, Exchange::whereDate('gift_date', '<=', now()->subDays(30)->toDateString())->count());
        $this->artisan('events:purge')->assertSuccessful();
        $this->assertDatabaseCount('exchanges', 0);
        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('deliveries', 0);
    }

    public function test_untrusted_strings_are_data_and_sensitive_fields_are_absent(): void
    {
        $name = '<script>alert(1)</script>';
        $this->manager()->post(route('exchanges.invite', $this->event), ['name' => $name, 'email' => 'evil@example.test', 'access_hash' => 'attacker', 'access_version' => 99])->assertSessionDoesntHaveErrors();
        $p = $this->event->participants()->where('email', 'evil@example.test')->firstOrFail();
        $this->assertSame(1, $p->access_version);
        $this->assertNull($p->confirmed_at);
        $this->get(route('exchanges.manage', $this->event))->assertInertia(fn (Assert $page) => $page->where('participants.3.name', $name)->missing('participants.3.email')->missing('participants.3.access_token')->missing('exchange.access_token'));
        $this->post(route('exchanges.invite', $this->event), ['name' => 'x', 'email' => "x\x27 OR 1=1 --"])->assertSessionHasErrors('email');
        $this->post(route('exchanges.invite', $this->event), ['name' => str_repeat('a', 81), 'email' => 'long@example.test'])->assertSessionHasErrors('name');
        $this->post(route('exchanges.invite', $this->event), ['name' => ['a'], 'email' => ['a']])->assertSessionHasErrors(['name', 'email']);
        $this->assertDatabaseCount('participants', 4);
    }

    public function test_capacity_and_participant_field_injection(): void
    {
        $p = $this->people[0];
        $this->post(route('access.activate', $p->access_token));
        $this->patch(route('participants.update', $this->event), ['name' => '名 🎁', 'wishes' => '<img src=x onerror=alert(1)>', 'email' => 'attack@example.test', 'recipient_id' => $p->id, 'access_version' => 99])->assertSessionDoesntHaveErrors();
        $this->assertSame($p->email, $p->fresh()->email);
        $this->assertNull($p->fresh()->recipient_id);
        for ($i = 4; $i <= 100; $i++) {
            $token = Str::random(64);
            $this->event->participants()->create(['name' => "P$i", 'email' => "p$i@example.test", 'access_token' => $token, 'access_hash' => hash('sha256', $token)]);
        }
        $this->manager()->post(route('exchanges.invite', $this->event), ['name' => 'Overflow', 'email' => 'overflow@example.test'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('participants', 100);
    }

    public function test_logout_invalidates_access_and_scanners_do_not_confirm(): void
    {
        $p = $this->people[0];
        $p->update(['confirmed_at' => null]);
        $this->get(route('access.show', $p->access_token))->assertOk();
        $this->assertNull($p->fresh()->confirmed_at);
        $this->post(route('access.activate', $p->access_token));
        $this->post('/logout')->assertRedirect();
        $this->get(route('participants.show', $this->event))->assertForbidden();
        $this->get('/access/short')->assertNotFound();
    }

    public function test_invalid_draw_sizes_and_duplicates_are_rejected(): void
    {
        foreach ([[], [1, 2], [1, 2, 2], range(1, 101)] as $ids) {
            try {
                (new DrawNames)->assign($ids, []);
                $this->fail('Invalid group accepted');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('draw', $e->errors());
            }
        }
    }

    public function test_exclusions_can_only_be_removed_in_their_own_open_event(): void
    {
        $this->manager()->post(route('exclusions.store', $this->event), ['giver_id' => $this->people[0]->id, 'recipient_id' => $this->people[1]->id]);
        $id = DB::table('exclusions')->value('id');
        $this->delete(route('exclusions.destroy', [$this->event, $id]))->assertRedirect(route('exchanges.manage', $this->event));
        $this->assertDatabaseCount('exclusions', 0);
        $this->draw();
        $this->delete(route('exclusions.destroy', [$this->event, $id]))->assertConflict();
    }

    public function test_organizer_email_link_and_repeat_reveal_are_safe(): void
    {
        $this->get(route('access.show', $this->event->access_token))->assertRedirect(route('login'));
        $this->post(route('access.activate', $this->event->access_token))->assertRedirect(route('login'));
        $this->draw();
        $this->post(route('access.activate', $this->people[0]->access_token));
        $this->post(route('participants.reveal', $this->event));
        $opened = $this->people[0]->fresh()->revealed_at;
        $this->travel(1)->minutes();
        $this->post(route('participants.reveal', $this->event));
        $this->assertTrue($opened->equalTo($this->people[0]->fresh()->revealed_at));
    }

    public function test_public_join_cannot_bypass_manager_resend_quota(): void
    {
        $this->manager();
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('participants.resend', [$this->event, $this->people[0]->id]));
        }
        $this->post(route('participants.store', $this->event), ['name' => 'X', 'email' => $this->people[0]->email])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('deliveries', 3);
    }

    public function test_request_rate_limits_reject_exhaustion_without_changing_state(): void
    {
        $this->manager();
        for ($i = 0; $i < 30; $i++) {
            $this->post(route('participants.resend', [$this->event, 999999]))->assertNotFound();
        }
        $this->post(route('exchanges.cancel', $this->event), ['confirm' => 1])->assertStatus(429);
        $this->assertNull($this->event->fresh()->cancelled_at);
        for ($i = 0; $i < 60; $i++) {
            $this->get('/access/'.str_repeat('x', 64))->assertNotFound();
        }
        $this->get(route('access.show', $this->people[0]->access_token))->assertStatus(429);
    }

    public function test_delivery_cancellation_legacy_and_sanitized_transport_errors(): void
    {
        $d = Delivery::enqueue($this->event, $this->people[0], 'join');
        $this->manager()->post(route('exchanges.cancel', $this->event), ['confirm' => 1]);
        (new SendAccessLink($d->id))->handle();
        $this->assertSame('obsolete', $d->fresh()->status);
        $cancel = Delivery::where('kind', 'cancel')->first();
        (new SendAccessLink($cancel->id))->handle();
        $this->assertSame('accepted', $cancel->fresh()->status);
        $message = Mail::mailer()->getSymfonyTransport()->messages()->first()->getOriginalMessage()->getHtmlBody();
        $this->assertStringNotContainsString('/access/', $message);
        $legacy = new SendAccessLink(0);
        unset($legacy->deliveryId);
        $legacy->handle();
        $legacy->failed(null);
        $this->assertSame([30, 120, 300], $legacy->backoff());
        $this->event->update(['cancelled_at' => null]);
        $this->event->refresh();
        $d = Delivery::enqueue($this->event, $this->people[0], 'join');
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('private@example.test'));
        try {
            (new SendAccessLink($d->id))->handle();
            $this->fail('SMTP failure swallowed');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('private@example.test', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
        $this->assertSame('pending', $d->fresh()->status);
    }

    public function test_missing_legacy_organizer_token_is_not_mailed(): void
    {
        $this->event->update(['access_token' => null]);
        $d = Delivery::enqueue($this->event, null, 'organizer');
        (new SendAccessLink($d->id))->handle();
        $this->assertSame('obsolete', $d->fresh()->status);
    }

    public function test_inertia_validation_returns_to_current_management_page_without_referer(): void
    {
        $this->manager();
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request())])->get(route('exchanges.manage', $this->event))->assertOk();
        $this->patch(route('exchanges.update', $this->event), ['name' => ''])->assertRedirect('/events/'.$this->event->uuid.'/manage')->assertSessionHasErrors('name');
    }
}
