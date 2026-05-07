<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_agenda_profiles', function (Blueprint $table) {
            $table->boolean('actif')->default(true)->after('remarque');
        });
    }

    public function down(): void
    {
        Schema::table('user_agenda_profiles', function (Blueprint $table) {
            $table->dropColumn('actif');
        });
    }
};
