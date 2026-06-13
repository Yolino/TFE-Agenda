<?php

namespace Tests\Unit;

use App\Models\Planning;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlanningStatusMappingTest extends TestCase
{
    public static function statutProvider(): array
    {
        return [
            'bureau'       => ['bureau', 1],
            'tele_travail' => ['tele_travail', 2],
            'conge'        => ['conge', 3],
            'recup'        => ['recup', 4],
            'css'          => ['css', 5],
            'indisponible' => ['indisponible', 6],
            'neant'        => ['neant', 7],
            'maladie'      => ['maladie', 8],
            'jour_ferie'   => ['jour_ferie', 9],
            'custom'       => ['custom', 10],
        ];
    }

    #[DataProvider('statutProvider')]
    public function test_le_libelle_est_traduit_en_identifiant(string $libelle, int $identifiant): void
    {
        $planning = new Planning();
        $planning->status = $libelle;

        $this->assertSame($identifiant, $planning->status_id);
    }

    #[DataProvider('statutProvider')]
    public function test_l_identifiant_est_retraduit_en_libelle(string $libelle, int $identifiant): void
    {
        $planning = new Planning();
        $planning->status_id = $identifiant;

        $this->assertSame($libelle, $planning->status);
    }

    public function test_un_libelle_inconnu_ne_produit_aucun_identifiant(): void
    {
        $planning = new Planning();
        $planning->status = 'statut_inexistant';

        $this->assertNull($planning->status_id);
    }

    public function test_un_identifiant_absent_ne_produit_aucun_libelle(): void
    {
        $planning = new Planning();
        $planning->status_id = 42;

        $this->assertNull($planning->status);
    }
}
