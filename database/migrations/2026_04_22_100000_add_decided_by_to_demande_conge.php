<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demande_conge', function (Blueprint $table) {
            $table->foreignId('decided_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->after('decided_by');
        });
    }

    public function down(): void
    {
        Schema::table('demande_conge', function (Blueprint $table) {
            $table->dropForeign(['decided_by']);
            $table->dropColumn(['decided_by', 'decided_at']);
        });
    }
};
