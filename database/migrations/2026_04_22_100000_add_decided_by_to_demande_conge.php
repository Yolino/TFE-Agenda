<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demande_conge', function (Blueprint $table) {
            $table->unsignedBigInteger('decided_by')->nullable()->after('status')->comment('FK logique vers bti.users.id');
            $table->timestamp('decided_at')->nullable()->after('decided_by');
            $table->index('decided_by');
        });
    }

    public function down(): void
    {
        Schema::table('demande_conge', function (Blueprint $table) {
            $table->dropIndex(['decided_by']);
            $table->dropColumn(['decided_by', 'decided_at']);
        });
    }
};
