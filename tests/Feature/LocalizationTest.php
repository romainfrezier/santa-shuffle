<?php

namespace Tests\Feature;

use App\Jobs\SendAccessLink;
use App\Models\Delivery;
use App\Models\Exchange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_language_is_negotiated_and_session_selection_wins(): void
    {
        $this->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8'])->get('/login')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'de'));
        $this->from('/login')->post('/locale', ['locale' => 'es'])->assertRedirect('/login');
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('locale', 'es'));
        $this->post('/locale', ['locale' => '../../en'])->assertSessionHasErrors('locale');
        $this->assertSame('es', session('locale'));
    }

    public function test_unsupported_browser_languages_fall_back_to_english(): void
    {
        $this->withHeaders(['Accept-Language' => 'ja-JP'])->get('/login')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_validation_and_login_mail_are_localized_in_each_language(): void
    {
        foreach (['en' => 'Your sign-in code', 'fr' => 'Votre code de connexion', 'es' => 'Tu código de acceso', 'de' => 'Dein Anmeldecode'] as $locale => $subject) {
            $this->withSession(['locale' => $locale])->post('/login/code', ['email' => 'invalid'])
                ->assertSessionHasErrors(['email' => __('validation.email', ['attribute' => __('validation.attributes.email')], $locale)]);
            Mail::shouldReceive('send')->once()->withArgs(function ($view, $data, $callback) use ($subject, $locale) {
                $this->assertSame($locale, app()->getLocale());
                $this->assertStringContainsString('lang="'.$locale.'"', view($view, $data)->render());
                $message = new Message(new Email);
                $callback($message);
                $this->assertSame($subject.' · Santa Shuffle', $message->getSymfonyMessage()->getSubject());

                return true;
            });
            $this->post('/login/code', ['email' => $locale.'@example.test'])->assertRedirect('/login');
        }
    }

    public function test_event_delivery_retains_its_language_outside_an_http_request(): void
    {
        Queue::fake();
        $this->withSession(['organizer_email' => 'owner@example.test', 'locale' => 'es'])
            ->post('/events', ['name' => 'Team', 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20])->assertRedirect();
        $delivery = Delivery::firstOrFail();
        $this->assertSame('es', $delivery->locale);
        app()->setLocale('en');
        Mail::shouldReceive('send')->once()->withArgs(function ($view, $data, $callback) {
            $this->assertSame('es', app()->getLocale());
            $this->assertStringContainsString('lang="es"', view($view, $data)->render());

            return true;
        });
        (new SendAccessLink($delivery->id))->handle();
        $this->assertSame('en', app()->getLocale());
        $this->assertSame('accepted', $delivery->fresh()->status);
    }

    public function test_participant_language_is_saved_only_by_registration_or_authenticated_access(): void
    {
        Queue::fake();
        $this->withSession(['organizer_email' => 'owner@example.test', 'locale' => 'fr'])
            ->post('/events', ['name' => 'Team', 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20]);
        $event = Exchange::firstOrFail();
        $this->withSession(['locale' => 'es'])->post(route('participants.store', $event), ['name' => 'Alex', 'email' => 'alex@example.test'])->assertRedirect();
        $person = $event->participants()->firstOrFail();
        $this->assertSame('es', $person->locale);
        $this->assertSame('es', Delivery::where('participant_id', $person->id)->firstOrFail()->locale);

        $this->withSession(['locale' => 'en'])->post(route('participants.store', $event), ['name' => 'Intruder', 'email' => $person->email])->assertRedirect();
        $this->assertSame('es', $person->fresh()->locale);
        $this->assertSame('Alex', $person->fresh()->name);

        $this->withSession(['locale' => 'de'])->post(route('access.activate', $person->access_token))->assertRedirect();
        $this->assertSame('de', $person->fresh()->locale);
        $delivery = Delivery::enqueue($event, $person->fresh(), 'draw');
        $this->assertSame('de', $delivery->locale);
        $this->assertSame('fr', $event->fresh()->locale);
    }

    public function test_locale_migration_preserves_existing_data_and_rolls_back(): void
    {
        Queue::fake();
        $this->withSession(['organizer_email' => 'owner@example.test'])
            ->post('/events', ['name' => 'Legacy event', 'gift_date' => now()->addMonth()->toDateString(), 'budget' => 20]);
        $event = Exchange::firstOrFail();
        $migration = require database_path('migrations/2026_11_03_000005_add_locales.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('exchanges', 'locale'));
        $this->assertSame('Legacy event', $event->fresh()->name);
        $this->assertDatabaseCount('deliveries', 1);
        $migration->up();
        $this->assertSame('fr', $event->fresh()->locale);
        $this->assertSame('fr', Delivery::firstOrFail()->locale);
    }

    public function test_error_pages_keep_the_selected_language_before_locale_middleware_runs(): void
    {
        $this->withSession(['locale' => 'es']);
        foreach (['missing-event', '00000000-0000-4000-8000-000000000000'] as $uuid) {
            $this->get('/events/'.$uuid.'/manage')->assertNotFound()
                ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404)->where('locale', 'es'))
                ->assertSee('lang="es"', false);
        }
        $this->get('/does-not-exist')->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'es'))
            ->assertSee('lang="es"', false);
        $this->post('/events', [])->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->where('status', 403)->where('locale', 'es'));
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/events', [])->assertForbidden();
        }
        $this->post('/events', [])->assertStatus(429)
            ->assertInertia(fn (Assert $page) => $page->where('status', 429)->where('locale', 'es'))
            ->assertSee('lang="es"', false);
    }
}
