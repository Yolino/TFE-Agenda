<?php

namespace Tests\Unit;

use App\Models\Departement;
use App\Models\User;
use App\Models\UserAgendaProfile;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Les rôles déterminent qui peut accepter des congés, consulter les logs, etc.
 * Cette logique est donc critique pour la sécurité de l'application.
 *
 * On la teste SANS base de données : les relations Eloquent (profile,
 * departements) sont injectées en mémoire via setRelation(), ce qui isole la
 * règle métier de toute infrastructure et rend le test rapide et déterministe.
 */
class UserRolesTest extends TestCase
{
    /** Construit un utilisateur dont seule la relation "profile" est définie. */
    private function utilisateurAvecProfil(?bool $estAdmin): User
    {
        $user = new User();
        $user->setRelation(
            'profile',
            $estAdmin === null ? null : new UserAgendaProfile(['is_admin' => $estAdmin])
        );

        return $user;
    }

    /** Construit un utilisateur rattaché aux départements dont on donne les lettres. */
    private function utilisateurAvecDepartements(array $lettres): User
    {
        $user = new User();
        $user->setRelation('departements', new Collection(array_map(
            fn (string $lettre) => new Departement(['letter' => $lettre]),
            $lettres
        )));

        return $user;
    }

    /**
     * @return array<string, array{0: ?bool, 1: bool}>
     */
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

    /**
     * @return array<string, array{0: array<int, string>, 1: bool}>
     */
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

    /**
     * @return array<string, array{0: bool, 1: array<int, string>, 2: bool}>
     */
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
