<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait BuildsTestSchema
{
    protected function buildTestSchema(): void
    {
        $this->buildLocalSchema();
        $this->buildBtiSchema();
    }

    private function buildLocalSchema(): void
    {
        $schema = Schema::connection('mysql');

        $schema->create('demande_conge', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->decimal('nb_jours', 5, 1);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('en_cours');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
        });

        $schema->create('justificatif_absence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('nb_jours');
            $table->string('certificat_medical');
            $table->timestamps();
        });

        $schema->create('plannings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->time('start_time_morning')->nullable();
            $table->time('end_time_morning')->nullable();
            $table->time('start_time_afternoon')->nullable();
            $table->time('end_time_afternoon')->nullable();
            $table->time('actual_start_time_morning')->nullable();
            $table->time('actual_end_time_morning')->nullable();
            $table->time('actual_start_time_afternoon')->nullable();
            $table->time('actual_end_time_afternoon')->nullable();
            $table->unsignedTinyInteger('status_id')->nullable();
            $table->unsignedBigInteger('custom_type_id')->nullable();
            $table->unsignedBigInteger('demande_conge_id')->nullable();
            $table->timestamps();
        });

        $schema->create('planning_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('day_of_week');
            $table->time('start_time_morning')->nullable();
            $table->time('end_time_morning')->nullable();
            $table->time('start_time_afternoon')->nullable();
            $table->time('end_time_afternoon')->nullable();
            $table->unsignedTinyInteger('status_id')->nullable();
            $table->timestamps();
        });

        $schema->create('logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('action');
            $table->string('description')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    private function buildBtiSchema(): void
    {
        Schema::connection('bti')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('firstname')->nullable();
            $table->string('alias')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('acces_level')->nullable();
            $table->string('avatar')->nullable();
            $table->string('theme')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection('bti')->create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('letter')->nullable();
            $table->string('nom')->nullable();
            $table->timestamps();
        });

        Schema::connection('bti')->create('departement_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('departement_id');
        });

        Schema::connection('bti')->create('pivot_a_u', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('agence_id');
        });
    }
}
