<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\DatabaseTestCase;

class CronRoutesAuthTest extends DatabaseTestCase
{
    private const CRON_USER = 'cron-robot';
    private const CRON_PASSWORD = 'cron-s3cret';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('crons.user', self::CRON_USER);
        config()->set('crons.password', self::CRON_PASSWORD);
        config()->set('crons.emails_enabled', false);

        Mail::fake();
    }

    public static function cronRoutesProvider(): array
    {
        return [
            'planning hebdomadaire' => ['/cron/planning-hebdo'],
            'congés en attente'     => ['/cron/conges-attente'],
            'test des CRON'         => ['/cron/test'],
        ];
    }

    #[DataProvider('cronRoutesProvider')]
    public function test_une_route_cron_est_refusee_sans_authentification(string $route): void
    {
        $this->get($route)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertHeader('WWW-Authenticate', 'Basic');
    }

    #[DataProvider('cronRoutesProvider')]
    public function test_une_route_cron_est_refusee_avec_de_mauvais_identifiants(string $route): void
    {
        $this->get($route, $this->basicAuth(self::CRON_USER, 'mauvais-mot-de-passe'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    #[DataProvider('cronRoutesProvider')]
    public function test_une_route_cron_est_accessible_avec_les_bons_identifiants(string $route): void
    {
        $this->get($route, $this->basicAuth(self::CRON_USER, self::CRON_PASSWORD))
            ->assertOk();
    }

    private function basicAuth(string $user, string $password): array
    {
        return ['Authorization' => 'Basic ' . base64_encode("{$user}:{$password}")];
    }
}
