<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demande_conge', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('FK logique vers bti.users.id');
            $table->enum('type', ['recup', 'conge', 'css', 'visite', 'autre']);
            $table->integer('nb_jours');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['en_cours', 'envoyee', 'acceptee', 'refusee'])->default('en_cours');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_conge');
    }
};
