<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    private array $previousEnvironment = [];

    protected function setUp(): void
    {
        $this->setEnvironment('TRUSTED_PROXIES', '172.20.0.2, 2001:db8:1::/64');
        parent::setUp();
        $this->registerProbe();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            foreach ($this->previousEnvironment as $name => $state) {
                putenv($state['process'] === false ? $name : $name.'='.$state['process']);
                unset($_ENV[$name], $_SERVER[$name]);
                $_ENV = $state['env'] + $_ENV;
                $_SERVER = $state['server'] + $_SERVER;
            }
        }
    }

    public function test_clients_behind_a_trusted_proxy_have_separate_login_and_creation_quotas(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.20.0.2'])
            ->withHeader('X-Forwarded-For', '203.0.113.10');
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/login/code', ['email' => 'invalid-'.$attempt])->assertUnprocessable();
        }
        $this->postJson('/login/code', ['email' => 'invalid-next'])->assertStatus(429);
        $this->withHeader('X-Forwarded-For', '203.0.113.11')
            ->postJson('/login/code', ['email' => 'invalid-next'])->assertUnprocessable();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/events', [])->assertForbidden();
        }
        $this->post('/events', [])->assertStatus(429);
        $this->withHeader('X-Forwarded-For', '203.0.113.12')->post('/events', [])->assertForbidden();
    }

    public function test_an_untrusted_caller_cannot_evade_quotas_by_spoofing_forwarded_headers(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withHeader('X-Forwarded-For', '198.51.100.'.$attempt)
                ->post('/events', [])->assertForbidden();
        }
        $this->withHeader('X-Forwarded-For', '198.51.100.99')->post('/events', [])->assertStatus(429);
        $this->getJson('/_proxy-test')->assertJsonPath('ip', '203.0.113.10');
    }

    public function test_forwarded_chains_use_the_rightmost_untrusted_client_and_support_explicit_ipv6_cidrs(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.20.0.2'])
            ->withHeader('X-Forwarded-For', '198.51.100.99, 203.0.113.10')
            ->getJson('/_proxy-test')->assertJsonPath('ip', '203.0.113.10');
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:1::2'])
            ->withHeader('X-Forwarded-For', '2001:db8:2::10')
            ->getJson('/_proxy-test')->assertJsonPath('ip', '2001:db8:2::10');
    }

    public function test_no_proxy_is_trusted_by_default(): void
    {
        $this->setEnvironment('TRUSTED_PROXIES', '');
        $this->refreshApplication();
        $this->registerProbe();
        $this->assertSame([], config('app.trusted_proxies'));
        $this->withServerVariables(['REMOTE_ADDR' => '172.20.0.2'])
            ->withHeader('X-Forwarded-For', '203.0.113.10')
            ->getJson('/_proxy-test')->assertJsonPath('ip', '172.20.0.2');
        foreach (['attacker.on-forge.com', 'attacker.on-vapor.com'] as $host) {
            $this->getJson('http://'.$host.'/_proxy-test')->assertJsonPath('host', $host)
                ->assertJsonPath('ip', '172.20.0.2');
        }
    }

    public function test_forwarded_origin_headers_cannot_change_the_canonical_production_url(): void
    {
        config(['app.url' => 'https://santa.example.test']);
        $this->app['env'] = 'production';
        (new AppServiceProvider($this->app))->boot();
        $this->withServerVariables(['REMOTE_ADDR' => '172.20.0.2'])->withHeaders([
            'X-Forwarded-For' => '203.0.113.10',
            'X-Forwarded-Host' => 'attacker.example',
            'X-Forwarded-Proto' => 'http',
            'X-Forwarded-Port' => '1234',
            'X-Forwarded-Prefix' => '/attacker',
        ])->getJson('/_proxy-test')
            ->assertJsonPath('ip', '203.0.113.10')
            ->assertJsonPath('login', 'https://santa.example.test/login')
            ->assertJsonPath('host', 'santa.example.test');
    }

    public function test_proxy_configuration_is_loaded_from_the_configuration_cache(): void
    {
        $cachePath = tempnam(sys_get_temp_dir(), 'santa-proxy-config-');
        $this->setEnvironment('APP_CONFIG_CACHE', $cachePath);
        try {
            unlink($cachePath);
            $this->artisan('config:cache')->assertSuccessful();
            $this->setEnvironment('TRUSTED_PROXIES', '');
            $this->refreshApplication();
            $this->registerProbe();
            $this->assertTrue($this->app->configurationIsCached());
            $this->withServerVariables(['REMOTE_ADDR' => '172.20.0.2'])
                ->withHeader('X-Forwarded-For', '203.0.113.10')
                ->getJson('/_proxy-test')->assertJsonPath('ip', '203.0.113.10');
        } finally {
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
        }
    }

    private function registerProbe(): void
    {
        Route::get('/_proxy-test', fn (Request $request) => [
            'ip' => $request->ip(),
            'host' => $request->host(),
            'login' => route('login'),
        ]);
    }

    private function setEnvironment(string $name, string $value): void
    {
        if (! array_key_exists($name, $this->previousEnvironment)) {
            $this->previousEnvironment[$name] = [
                'process' => getenv($name),
                'env' => array_intersect_key($_ENV, [$name => true]),
                'server' => array_intersect_key($_SERVER, [$name => true]),
            ];
        }
        // Drop Dotenv ownership so later boots cannot reload .env over this override.
        Env::getRepository()->clear($name);
        // Set every adapter directly, including values inherited from CI.
        putenv($name.'='.$value);
        $_ENV[$name] = $_SERVER[$name] = $value;
    }
}
