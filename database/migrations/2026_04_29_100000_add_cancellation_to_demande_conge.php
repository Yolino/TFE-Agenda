<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Étendre l'enum status pour accepter "annulee"
        DB::statement("ALTER TABLE demande_conge MODIFY COLUMN status ENUM('en_cours','envoyee','acceptee','refusee','annulee') NOT NULL DEFAULT 'en_cours'");

        Schema::table('demande_conge', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('decided_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('demande_conge', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by']);
        });

        DB::statement("ALTER TABLE demande_conge MODIFY COLUMN status ENUM('en_cours','envoyee','acceptee','refusee') NOT NULL DEFAULT 'en_cours'");
    }
};
