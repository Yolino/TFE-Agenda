<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des LOGS MÉTIERS (actions sensibles).
 *
 * Stockée sur la connexion "mysql" (base locale de l'application), au même
 * titre que user_agenda_profiles. On NE met pas de clé étrangère vers les
 * utilisateurs car ceux-ci vivent dans une autre base (connexion "bti").
 * On conserve donc à la fois l'id (user_id) ET un instantané du nom
 * (user_name) pour garder une trace lisible même si l'utilisateur est
 * supprimé/modifié côté base globale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('logs', function (Blueprint $table) {
            $table->id();

            // Identité de l'auteur de l'action
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();

            // Nature de l'action, ex : "conge.accepted", "user.deactivated"
            $table->string('action')->index();
            $table->string('description')->nullable();

            // Donnée concernée (relation polymorphe "légère", sans contrainte FK)
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Données concernées (payload libre : anciennes/nouvelles valeurs, etc.)
            $table->json('properties')->nullable();

            // created_at est indexé : il sert au filtrage par date ET à la purge des 6 mois.
            $table->timestamps();
            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('logs');
    }
};
