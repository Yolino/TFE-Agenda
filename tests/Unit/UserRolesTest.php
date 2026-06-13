<?php

namespace Tests\Unit;

use App\Models\Departement;
use App\Models\User;
use App\Models\UserAgendaProfile;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    private function utilisateurAvecProfil(?bool $estAdmin): User
    {
        $user = new User();
        $user->setRelation(
            'profile',
            $estAdmin === null ? null : new UserAgendaProfile(['is_admin' => $estAdmin])
        );

        return $user;
    }

    private function utilisateurAvecDepartements(array $lettres): User
    {
        $user = new User();
        $user->setRelation('departements', new Collection(array_map(
            fn (string $lettre) => new Departement(['letter' => $lettre]),
            $lettres
        )));

        return $user;
    }

    public static function adminProvider(): array
    {
        return [
            'profil administrateur'     => [true, true],
            'profil non administrateur' => [false, false],
            'aucun profil'              => [null, false],
        ];
    }

    #[DataProvider('adminProvider')]
    public function test_is_admin_reflete_le_drapeau_du_profil(?bool $estAdmin, bool $attendu): void
    {
        $this->assertSame($attendu, $this->utilisateurAvecProfil($estAdmin)->is_admin());
    }

    public static function directeurProvider(): array
    {
        return [
            'rattaché à la Direction (D)' => [['I', 'D'], true],
            'uniquement la lettre D'      => [['D'], true],
            'aucun département Direction' => [['I', 'C'], false],
            'aucun département'           => [[], false],
        ];
    }

    #[DataProvider('directeurProvider')]
    public function test_is_directeur_depend_du_departement_direction(array $lettres, bool $attendu): void
    {
        $this->assertSame($attendu, $this->utilisateurAvecDepartements($lettres)->is_directeur());
    }

    public static function accesLogsProvider(): array
    {
        return [
            'administrateur seul'    => [true, ['I'], true],
            'directeur seul'         => [false, ['D'], true],
            'administrateur ET directeur' => [true, ['D'], true],
            'ni admin ni directeur'  => [false, ['I'], false],
        ];
    }

    #[DataProvider('accesLogsProvider')]
    public function test_l_acces_aux_logs_est_reserve_aux_admins_ou_directeurs(
        bool $estAdmin,
        array $lettres,
        bool $attendu
    ): void {
        $user = new User();
        $user->setRelation('profile', new UserAgendaProfile(['is_admin' => $estAdmin]));
        $user->setRelation('departements', new Collection(array_map(
            fn (string $lettre) => new Departement(['letter' => $lettre]),
            $lettres
        )));

        $this->assertSame($attendu, $user->canAccessLogs());
    }

    private function utilisateur(bool $estAdmin, array $lettres): User
    {
        $user = new User();
        $user->setRelation('profile', new UserAgendaProfile(['is_admin' => $estAdmin]));
        $user->setRelation('departements', new Collection(array_map(
            fn (string $lettre) => new Departement(['letter' => $lettre]),
            $lettres
        )));

        return $user;
    }

    public static function adminOuDirecteurProvider(): array
    {
        return [
            'administrateur seul' => [true, ['I'], true],
            'directeur seul'      => [false, ['D'], true],
            'admin et directeur'  => [true, ['D'], true],
            'ni l\'un ni l\'autre' => [false, ['I'], false],
        ];
    }

    #[DataProvider('adminOuDirecteurProvider')]
    public function test_can_manage_users_pour_admin_ou_directeur(bool $estAdmin, array $lettres, bool $attendu): void
    {
        $this->assertSame($attendu, $this->utilisateur($estAdmin, $lettres)->canManageUsers());
    }

    #[DataProvider('adminOuDirecteurProvider')]
    public function test_can_edit_global_planning_pour_admin_ou_directeur(bool $estAdmin, array $lettres, bool $attendu): void
    {
        $this->assertSame($attendu, $this->utilisateur($estAdmin, $lettres)->canEditGlobalPlanning());
    }

    public static function gestionCongesProvider(): array
    {
        return [
            'directeur'            => [false, ['D'], true],
            'admin non directeur'  => [true, ['I'], false],
            'ni l\'un ni l\'autre' => [false, ['I'], false],
        ];
    }

    #[DataProvider('gestionCongesProvider')]
    public function test_can_manage_conges_reserve_aux_directeurs(bool $estAdmin, array $lettres, bool $attendu): void
    {
        $this->assertSame($attendu, $this->utilisateur($estAdmin, $lettres)->canManageConges());
    }

    public function test_un_directeur_n_a_pas_d_agenda_personnel(): void
    {
        $this->assertFalse($this->utilisateur(false, ['D'])->hasPersonalAgenda());
        $this->assertTrue($this->utilisateur(true, ['I'])->hasPersonalAgenda());
        $this->assertTrue($this->utilisateur(false, ['I'])->hasPersonalAgenda());
    }

    public function test_la_route_d_accueil_depend_du_role(): void
    {
        $this->assertSame('planning', $this->utilisateur(false, ['D'])->homeRoute());
        $this->assertSame('mon-planning.index', $this->utilisateur(true, ['I'])->homeRoute());
        $this->assertSame('mon-planning.index', $this->utilisateur(false, ['C'])->homeRoute());
    }

    public function test_is_etudiant_se_base_sur_le_niveau_d_acces(): void
    {
        $etudiant = new User();
        $etudiant->acces_level = User::ROLE_ETUDIANT;
        $this->assertTrue($etudiant->is_etudiant());

        $autre = new User();
        $autre->acces_level = 'U';
        $this->assertFalse($autre->is_etudiant());
    }

    public function test_la_fonction_est_le_nom_du_premier_departement(): void
    {
        $user = new User();
        $user->setRelation('departements', new Collection([
            new Departement(['nom' => 'Informatique']),
            new Departement(['nom' => 'Direction']),
        ]));
        $this->assertSame('Informatique', $user->fonction);

        $sansDepartement = new User();
        $sansDepartement->setRelation('departements', new Collection());
        $this->assertNull($sansDepartement->fonction);
    }
}
