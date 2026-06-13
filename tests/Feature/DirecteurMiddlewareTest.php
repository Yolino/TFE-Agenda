<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminOrDirecteur;
use App\Http\Middleware\HasPersonalAgenda;
use App\Http\Middleware\IsDirecteur;
use App\Models\Departement;
use App\Models\User;
use App\Models\UserAgendaProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DirecteurMiddlewareTest extends TestCase
{
    private function user(bool $estAdmin, array $lettres): User
    {
        $user = new User();
        $user->setRelation('profile', new UserAgendaProfile(['is_admin' => $estAdmin]));
        $user->setRelation('departements', new Collection(array_map(
            fn (string $lettre) => new Departement(['letter' => $lettre]),
            $lettres
        )));

        return $user;
    }

    private function passThrough(object $middleware, User $user)
    {
        $request = Request::create('/x', 'GET');
        $request->setUserResolver(fn () => $user);

        return $middleware->handle($request, fn () => new \Illuminate\Http\Response('ok'));
    }

    public function test_is_directeur_laisse_passer_un_directeur(): void
    {
        $response = $this->passThrough(new IsDirecteur(), $this->user(false, ['D']));
        $this->assertSame('ok', $response->getContent());
    }

    public function test_is_directeur_bloque_un_admin_non_directeur(): void
    {
        try {
            $this->passThrough(new IsDirecteur(), $this->user(true, ['I']));
            $this->fail('Un admin non directeur aurait dû être bloqué.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_admin_or_directeur_laisse_passer_admin_et_directeur(): void
    {
        $this->assertSame('ok', $this->passThrough(new AdminOrDirecteur(), $this->user(true, ['I']))->getContent());
        $this->assertSame('ok', $this->passThrough(new AdminOrDirecteur(), $this->user(false, ['D']))->getContent());
    }

    public function test_admin_or_directeur_bloque_les_autres(): void
    {
        try {
            $this->passThrough(new AdminOrDirecteur(), $this->user(false, ['I']));
            $this->fail('Un utilisateur sans rôle aurait dû être bloqué.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_has_personal_agenda_redirige_un_directeur_vers_le_planning(): void
    {
        $response = $this->passThrough(new HasPersonalAgenda(), $this->user(false, ['D']));

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('/planning', $response->getTargetUrl());
    }

    public function test_has_personal_agenda_laisse_passer_un_utilisateur_classique(): void
    {
        $response = $this->passThrough(new HasPersonalAgenda(), $this->user(false, ['I']));
        $this->assertSame('ok', $response->getContent());
    }

    public function test_le_gate_manage_planning_autorise_le_directeur_a_editer_le_planning_d_autrui(): void
    {
        $directeur = $this->user(false, ['D']);
        $directeur->id = 1;

        $this->assertTrue(Gate::forUser($directeur)->allows('manage-planning', 999));
    }

    public function test_le_gate_manage_planning_refuse_un_utilisateur_lambda_pour_autrui(): void
    {
        $lambda = $this->user(false, ['I']);
        $lambda->id = 2;

        $this->assertFalse(Gate::forUser($lambda)->allows('manage-planning', 999));
        $this->assertTrue(Gate::forUser($lambda)->allows('manage-planning', 2));
    }

    public function test_le_directeur_peut_poser_les_statuts_du_planning_general(): void
    {
        $this->actingAs($this->user(false, ['D']));

        $statusRule = (new \App\Http\Requests\PlanningRequest())->rules()['status'];

        $this->assertStringContainsString('conge', $statusRule);
        $this->assertStringContainsString('maladie', $statusRule);
    }

    public function test_un_utilisateur_classique_ne_peut_pas_poser_les_statuts_manager(): void
    {
        $this->actingAs($this->user(false, ['I']));

        $statusRule = (new \App\Http\Requests\PlanningRequest())->rules()['status'];

        $this->assertStringNotContainsString('conge', $statusRule);
        $this->assertStringNotContainsString('maladie', $statusRule);
    }
}
