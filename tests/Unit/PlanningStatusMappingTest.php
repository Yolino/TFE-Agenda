<?php

namespace Tests\Unit;

use App\Models\Planning;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Le planning convertit en permanence un libellé métier ("conge", "maladie"...)
 * en identifiant stocké en base (status_id), et inversement. Toute dérive de
 * cette table de correspondance casserait l'affichage ET les écritures du
 * planning : c'est un point sensible, testé exhaustivement et sans base de
 * données (logique pure).
 */
class PlanningStatusMappingTest extends TestCase
{
    /**
     * Source unique de vérité : chaque libellé et son identifiant attendu.
     *
     * @return array<string, array{0: string, 1: int}>
     */
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
