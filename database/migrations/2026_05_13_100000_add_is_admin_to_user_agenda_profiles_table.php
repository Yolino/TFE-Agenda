<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('user_agenda_profiles', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('actif');
        });

        $adminUserIds = DB::connection('bti')
            ->table('users')
            ->where('acces_level', 'like', '%A%')
            ->pluck('id');

        foreach ($adminUserIds as $userId) {
            DB::connection('mysql')->table('user_agenda_profiles')->updateOrInsert(
                ['user_id' => $userId],
                ['is_admin' => true, 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('user_agenda_profiles', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
