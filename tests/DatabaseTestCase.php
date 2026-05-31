<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsTestSchema;

/**
 * Cas de base pour les tests qui ont besoin d'une base de données.
 *
 * Les modèles ciblent explicitement deux connexions :
 *  - "mysql" : données locales de l'application ;
 *  - "bti"   : base globale externe (sans migrations dans ce dépôt).
 *
 * En test, on bascule CES DEUX connexions sur des bases SQLite en mémoire,
 * indépendantes et jetables (recréées à chaque test). Les relations
 * inter-connexions continuent de fonctionner : Eloquent émet une requête par
 * connexion, chacune sur sa propre base.
 *
 * Le schéma est fourni par BuildsTestSchema (point unique, DRY).
 */
abstract class DatabaseTestCase extends TestCase
{
    use BuildsTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['mysql', 'bti'] as $connection) {
            config()->set("database.connections.{$connection}", [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ]);

            DB::purge($connection);
        }

        $this->buildTestSchema();
    }
}
