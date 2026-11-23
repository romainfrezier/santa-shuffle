<?php

namespace Tests\Feature;

use App\Jobs\SendAccessLink;
use App\Models\Delivery;
use App\Models\Exchange;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SecretSantaTest extends TestCase
{
    use RefreshDatabase;

    private $realQueue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->realQueue = Queue::getFacadeRoot();
        Queue::fake();
    }

    private function event(): Exchange
    {
        return Exchange::create(['uuid' => (string) Str::uuid(), 'name' => 'Le Noël du studio', 'organizer_email' => 'owner@example.test', 'access_hash' => hash('sha256', Str::random(64)), 'gift_date' => now()->addMonth()->format('Y-m-d'), 'budget' => 20]);
    }

    private function people(Exchange $event, int $count = 3)
    {
        return collect(range(1, $count))->map(function ($n) use ($event) {
            $token = Str::random(64);

            return $event->participants()->create(['name' => 'Person '.$n, 'email' => "person{$n}@example.test", 'wishes' => 'Private wish '.$n, 'access_token' => $token, 'access_hash' => hash('sha256', $token), 'confirmed_at' => now()]);
        });
    }

    private function manage(Exchange $event): static
    {
        return $this->withSession(['organizer_email' => 'owner@example.test', "managed.{$event->id}" => 1]);
    }

    public function test_create_queues_access_and_grants_only_its_manager_session(): void
    {
        $response = $this->withSession(['organizer_email' => 'owner@example.test'])->post('/events', ['name' => 'Équipe', 'organizer_email' => 'Owner@example.test', 'gift_date' => now()->addMonth()->format('Y-m-d'), 'budget' => 20]);
        $event = Exchange::firstOrFail();
        $response->assertRedirect(route('exchanges.manage', $event));
        $this->assertSame('owner@example.test', $event->organizer_email);
        Queue::assertPushed(SendAccessLink::class, fn ($job) => Delivery::find($job->deliveryId)->kind === 'organizer');
        $this->get(route('exchanges.manage', $event))->assertOk();
        $this->get(route('exchanges.manage', $this->event()))->assertForbidden();
    }

    public function test_validation_rejects_invalid_event_inputs(): void
    {
        $this->withSession(['organizer_email' => 'owner@example.test'])->post('/events', ['name' => '', 'organizer_email' => 'bad', 'gift_date' => '2020-01-01', 'budget' => -1])->assertSessionHasErrors(['name', 'gift_date', 'budget']);
        $this->assertDatabaseCount('exchanges', 0);
    }

    public function test_invitation_never_exposes_participants_or_secrets(): void
    {
        $event = $this->event();
        $this->people($event);
        $this->get(route('exchanges.show', $event))->assertInertia(fn (Assert $page) => $page->component('Join')->where('count', 3)->missing('participants')->missing('exchange.organizer_email')->missing('exchange.access_hash'));
        $this->get(route('exchanges.manage', $event))->assertForbidden();
        $this->get(route('participants.show', $event))->assertForbidden();
        $this->post(route('participants.reveal', $event))->assertForbidden();
    }

    public function test_public_share_cards_are_localized_and_private_pages_use_only_site_metadata(): void
    {
        $event = $this->event();
        $event->update(['name' => 'Friends & <Team>', 'message' => 'Group-only message']);
        $person = $this->people($event, 1)->first();
        $this->withSession(['locale' => 'fr']);
        $head = fn (string $url, int $status = 200) => Str::before($this->get($url)->assertStatus($status)->getContent(), '</head>');

        $home = $head(route('home'));
        $this->assertStringContainsString('property="og:locale" content="fr_FR"', $home);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $home);
        $this->assertStringContainsString('property="og:image" content="'.url('/share-card.png').'"', $home);
        $image = getimagesize(public_path('share-card.png'));
        $this->assertSame([1200, 630, 'image/png'], [$image[0], $image[1], $image['mime']]);

        $inviteUrl = route('exchanges.show', $event);
        $invite = $head($inviteUrl.'?tracking=discard-me');
        $this->assertStringContainsString('property="og:title" content="'.e($event->name).' · Santa Shuffle"', $invite);
        $this->assertStringContainsString('property="og:description" content="'.e(__('messages.share_invitation', [], 'fr')).'"', $invite);
        $this->assertStringContainsString('property="og:url" content="'.$inviteUrl.'"', $invite);
        $this->assertStringNotContainsString('discard-me', $invite);
        $this->assertStringNotContainsString($event->message, $invite);

        $this->manage($event);
        $this->post(route('access.activate', $person->access_token))->assertRedirect();
        foreach ([
            route('access.show', $person->access_token), route('participants.show', $event),
            route('exchanges.manage', $event), route('exchanges.pending', $event),
        ] as $privateUrl) {
            $private = $head($privateUrl);
            $this->assertStringContainsString('property="og:url" content="'.route('home').'"', $private);
            foreach ([$event->uuid, e($event->name), $person->access_token, $person->name, $person->email, $person->wishes] as $sensitive) {
                $this->assertStringNotContainsString($sensitive, $private);
            }
        }
        $event->update(['cancelled_at' => now()]);
        $this->assertStringNotContainsString(e($event->name), $head($inviteUrl, 410));
    }

    public function test_only_current_participant_sessions_can_reopen_and_save_their_invitations(): void
    {
        $events = collect(range(1, 4))->map(fn () => $this->event());
        $people = $events->map(fn ($event) => $this->people($event, 1)->first());
        $savedLink = '/events/'.$events[0]->uuid.'/me/invitation';
        $this->get($savedLink)->assertForbidden();
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('envelopes', []));
        foreach ($people as $person) {
            $this->post(route('access.activate', $person->access_token))->assertRedirect();
        }
        $this->get(route('exchanges.show', $events[0]))->assertRedirect(route('participants.show', $events[0]));
        $this->get($savedLink)->assertRedirect(route('access.show', $people[0]->access_token));
        $people[1]->update(['access_version' => 2]);
        $events[2]->update(['cancelled_at' => now()]);
        $events[3]->update(['gift_date' => now()->subDays(31)]);
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('envelopes', [['uuid' => $events[0]->uuid, 'name' => $events[0]->name]]));
        $this->get(route('exchanges.show', $events[1]))->assertInertia(fn (Assert $page) => $page->component('Join'));
        $this->get('/events/'.$events[1]->uuid.'/me/invitation')->assertForbidden();
        $this->post('/logout')->assertRedirect('/');
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('envelopes', []));
        $this->get($savedLink)->assertForbidden();
        $this->post(route('access.activate', $people[0]->access_token))->assertRedirect(route('participants.show', $events[0]));
    }

    public function test_join_requires_explicit_email_confirmation_and_cannot_overwrite_an_existing_person(): void
    {
        $event = $this->event();
        $this->post(route('participants.store', $event), ['name' => 'Alice', 'email' => 'Alice@example.test', 'wishes' => 'Livres'])->assertRedirect(route('exchanges.pending', $event));
        $person = $event->participants()->firstOrFail();
        $this->assertNull($person->confirmed_at);
        $this->assertNotSame($person->access_token, $person->getRawOriginal('access_token'));
        $this->get(route('access.show', $person->access_token))->assertOk();
        $this->assertNull($person->fresh()->confirmed_at);
        $this->get(route('participants.show', $event))->assertForbidden();
        $this->post(route('participants.store', $event), ['name' => 'Mallory', 'email' => 'alice@example.test', 'wishes' => 'Replaced'])->assertRedirect();
        $this->assertDatabaseCount('participants', 1);
        $this->assertSame('Alice', $person->fresh()->name);
        $this->post(route('access.activate', $person->access_token))->assertRedirect(route('participants.show', $event));
        $this->assertNotNull($person->fresh()->confirmed_at);
        $this->get(route('participants.show', $event))->assertInertia(fn (Assert $p) => $p->where('participant.name', 'Alice')->where('recipient', null));
    }

    public function test_draw_respects_exclusions_is_private_and_cannot_be_repeated(): void
    {
        $event = $this->event();
        $people = $this->people($event, 4);
        $this->manage($event)->post(route('exclusions.store', $event), ['giver_id' => $people[0]->id, 'recipient_id' => $people[1]->id])->assertRedirect();
        $this->post(route('exchanges.draw', $event), ['confirm' => 1])->assertRedirect();
        $this->assertNotNull($event->fresh()->drawn_at);
        $pairs = $event->participants()->pluck('recipient_id', 'id')->all();
        $this->assertCount(4, array_unique($pairs));
        foreach ($pairs as $giver => $recipient) {
            $this->assertNotSame($giver, $recipient);
        }
        $this->assertNotSame($people[1]->id, $pairs[$people[0]->id]);
        Queue::assertPushed(SendAccessLink::class, 4);
        $this->get(route('exchanges.manage', $event))->assertInertia(fn (Assert $page) => $page->component('Manage')->has('participants', 4)->missing('participants.0.recipient_id')->missing('participants.0.email')->missing('participants.0.wishes')->missing('participants.0.access_token'));
        $this->post(route('exchanges.draw', $event), ['confirm' => 1])->assertSessionHasErrors('draw');
        $this->assertSame($pairs, $event->participants()->pluck('recipient_id', 'id')->all());
        Queue::assertPushed(SendAccessLink::class, 4);
    }

    public function test_impossible_draw_rolls_back_and_sends_no_email(): void
    {
        $event = $this->event();
        $people = $this->people($event);
        foreach ($people->skip(1) as $person) {
            DB::table('exclusions')->insert(['exchange_id' => $event->id, 'giver_id' => $people[0]->id, 'recipient_id' => $person->id]);
        }
        $this->manage($event)->post(route('exchanges.draw', $event), ['confirm' => 1])->assertSessionHasErrors('draw');
        $this->assertNull($event->fresh()->drawn_at);
        $this->assertSame(0, $event->participants()->whereNotNull('recipient_id')->count());
        Queue::assertNothingPushed();
    }

    public function test_organizer_can_confirm_only_the_matching_registration_before_the_draw(): void
    {
        $event = $this->event();
        [$person, $other] = $this->people($event);
        $person->update(['confirmed_at' => null]);
        $foreign = $this->people($this->event())->first();
        $path = "/events/{$event->uuid}/participants/{$person->id}/confirm";
        $data = ['email' => strtoupper($person->email)];

        $this->post($path, $data)->assertForbidden();
        $this->manage($event)->post("/events/{$event->uuid}/participants/{$foreign->id}/confirm", $data)->assertNotFound();
        $this->post($path, ['email' => $other->email])->assertSessionHasErrors('email');
        $this->assertNull($person->fresh()->confirmed_at);
        $this->post($path, $data)->assertRedirect(route('exchanges.manage', $event))->assertSessionDoesntHaveErrors();
        $confirmed = $person->fresh()->confirmed_at;
        $this->assertNotNull($confirmed);
        $this->travel(1)->minutes();
        $this->post($path, $data)->assertRedirect();
        $this->assertTrue($person->fresh()->confirmed_at->equalTo($confirmed));
        $this->get(route('participants.show', $event))->assertForbidden();
        Queue::assertNothingPushed();
        $this->post(route('exchanges.draw', $event), ['confirm' => 1])->assertSessionDoesntHaveErrors();
        $this->post($path, $data)->assertConflict();
    }

    public function test_homonyms_must_be_identified_before_draw_and_only_manager_can_rename(): void
    {
        $event = $this->event();
        [$first, $second] = $this->people($event);
        $first->update(['name' => 'Alex']);
        $second->update(['name' => ' alex ']);
        $path = "/events/{$event->uuid}/participants/{$second->id}/name";
        $data = ['name' => 'Alex Martin', 'email' => $second->email];
        $this->patch($path, $data)->assertForbidden();
        $this->manage($event)->post(route('exchanges.draw', $event), ['confirm' => 1])->assertSessionHasErrors('draw');
        $this->assertNull($event->fresh()->drawn_at);
        Queue::assertNothingPushed();
        $this->patch($path, [...$data, 'email' => $first->email])->assertSessionHasErrors('email');
        $this->assertSame(' alex ', $second->fresh()->name);
        $foreign = $this->people($this->event())->first();
        $this->patch("/events/{$event->uuid}/participants/{$foreign->id}/name", $data)->assertNotFound();
        $this->patch($path, [...$data, 'name' => ''])->assertSessionHasErrors('name');
        $this->patch($path, $data)->assertRedirect(route('exchanges.manage', $event));
        $this->assertSame('Alex Martin', $second->fresh()->name);
        $this->assertSame($data['email'], $second->fresh()->email);
        $this->get(route('exchanges.manage', $event))->assertInertia(fn (Assert $p) => $p
            ->where('participants.0.name', 'Alex')->where('participants.1.name', 'Alex Martin')
            ->missing('participants.0.email'));
        $this->post(route('exchanges.draw', $event), ['confirm' => 1])->assertSessionDoesntHaveErrors();
        $this->patch($path, [...$data, 'name' => 'Changed'])->assertConflict();
        $giver = $event->participants()->where('recipient_id', $second->id)->firstOrFail();
        $this->post(route('access.activate', $giver->access_token));
        $giver->update(['revealed_at' => now()]);
        $this->get(route('participants.show', $event))->assertInertia(fn (Assert $p) => $p
            ->where('recipient.name', 'Alex Martin')->missing('recipient.email'));
    }

    public function test_pending_confirmations_block_the_draw(): void
    {
        $event = $this->event();
        $people = $this->people($event);
        $people[0]->update(['confirmed_at' => null]);
        $this->manage($event)->post(route('exchanges.draw', $event), ['confirm' => 1])->assertSessionHasErrors('draw');
        $this->assertNull($event->fresh()->drawn_at);
        Queue::assertNothingPushed();
    }

    public function test_reveal_requires_own_session_and_an_explicit_action(): void
    {
        $event = $this->event();
        $people = $this->people($event);
        $this->manage($event)->post(route('exchanges.draw', $event), ['confirm' => 1]);
        $this->get(route('participants.show', $event))->assertForbidden();
        $this->post(route('access.activate', $people[0]->access_token));
        $this->get(route('participants.show', $event))->assertInertia(fn (Assert $p) => $p->where('recipient', null));
        $this->post(route('participants.reveal', $event))->assertRedirect();
        $recipient = $people[0]->fresh()->recipient;
        $this->get(route('participants.show', $event))->assertInertia(fn (Assert $p) => $p->where('recipient.name', $recipient->name)->where('recipient.wishes', $recipient->wishes)->missing('recipient.email')->missing('recipient.access_token')->missing('participant.recipient_id'));
        $this->get(route('participants.show', $this->event()))->assertForbidden();
    }

    public function test_cross_event_exclusions_and_post_draw_mutations_are_rejected(): void
    {
        $event = $this->event();
        $people = $this->people($event);
        $other = $this->people($this->event());
        $this->manage($event)->post(route('exclusions.store', $event), ['giver_id' => $people[0]->id, 'recipient_id' => $other[0]->id])->assertSessionHasErrors('recipient_id');
        $this->post(route('exchanges.draw', $event), ['confirm' => 1]);
        $this->post(route('participants.store', $event), ['name' => 'Late', 'email' => 'late@example.test'])->assertSessionHasErrors('email');
        $this->post(route('exclusions.store', $event), ['giver_id' => $people[0]->id, 'recipient_id' => $people[1]->id])->assertConflict();
        $this->delete(route('participants.destroy', [$event, $people[0]->id]))->assertConflict();
        $this->post(route('access.activate', $people[0]->access_token));
        $this->patch(route('participants.update', $event), ['name' => 'Changed', 'wishes' => 'New'])->assertConflict();
    }

    public function test_access_expiration_invalid_token_and_privacy_headers(): void
    {
        $event = $this->event();
        $people = $this->people($event);
        $this->get(route('access.show', Str::random(64)))->assertNotFound();
        $this->get('/')->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Frame-Options', 'DENY');
        $this->travel(3)->months();
        $this->post(route('access.activate', $people[0]->access_token))->assertStatus(410);
        $this->get(route('exchanges.show', $event))->assertStatus(410);
    }

    public function test_database_jobs_encrypt_access_links(): void
    {
        Queue::swap($this->realQueue);
        config(['queue.default' => 'database']);
        $token = Str::random(64);
        $event = $this->event();
        $person = $this->people($event)->first();
        Delivery::enqueue($event, $person, 'join');
        $payload = DB::table('jobs')->value('payload');
        $this->assertIsString($payload);
        $this->assertStringNotContainsString($person->access_token, $payload);
        $this->assertStringNotContainsString($person->email, $payload);
    }

    public function test_creation_joins_and_confirmations_do_not_consume_the_draw_rate_limit(): void
    {
        $this->withSession(['organizer_email' => 'owner@example.test'])->post('/events', ['name' => 'Test complet', 'organizer_email' => 'owner@example.test', 'gift_date' => now()->addMonth()->format('Y-m-d'), 'budget' => 20])->assertRedirect();
        $event = Exchange::firstOrFail();
        foreach (range(1, 3) as $number) {
            $this->post(route('participants.store', $event), ['name' => "Person $number", 'email' => "p$number@example.test"])->assertRedirect();
            $person = $event->participants()->where('email', "p$number@example.test")->firstOrFail();
            $this->get(route('access.show', $person->access_token))->assertOk();
            $this->post(route('access.activate', $person->access_token))->assertRedirect();
        }
        $this->post(route('exchanges.draw', $event), ['confirm' => 1])->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertNotNull($event->fresh()->drawn_at);
    }

    public function test_queue_failure_rolls_back_the_entire_draw(): void
    {
        $event = $this->event();
        $this->people($event);
        Queue::swap($this->realQueue);
        config(['queue.default' => 'database', 'queue.connections.database.table' => 'missing_jobs_table']);
        $this->withoutExceptionHandling();
        try {
            $this->manage($event)->post(route('exchanges.draw', $event), ['confirm' => 1]);
            $this->fail('Queue failure should abort the transaction.');
        } catch (QueryException $exception) {
            $this->assertNull($event->fresh()->drawn_at);
            $this->assertSame(0, $event->participants()->whereNotNull('recipient_id')->count());
        }
    }

    public function test_participant_can_be_removed_before_draw_with_exclusions(): void
    {
        $event = $this->event();
        $people = $this->people($event, 4);
        $this->manage($event)->post(route('exclusions.store', $event), ['giver_id' => $people[0]->id, 'recipient_id' => $people[1]->id]);
        $this->delete(route('participants.destroy', [$event, $people[0]->id]))->assertRedirect();
        $this->assertDatabaseCount('exclusions', 0);
        $this->post(route('access.activate', $people[0]->access_token))->assertNotFound();
        $this->post(route('exchanges.draw', $event), ['confirm' => 1])->assertRedirect()->assertSessionDoesntHaveErrors();
    }
}
