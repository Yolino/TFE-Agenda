<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agences_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('FK logique vers bti.users.id');
            $table->unsignedBigInteger('agence_id')->comment('FK logique vers bti.agences.id');
            $table->timestamps();

            $table->unique(['user_id', 'agence_id']);
            $table->index('user_id');
            $table->index('agence_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agences_users');
    }
};
