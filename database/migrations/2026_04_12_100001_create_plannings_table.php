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
        Schema::create('plannings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('FK logique vers bti.users.id');
            $table->date('date');
            $table->time('start_time_morning')->nullable();
            $table->time('end_time_morning')->nullable();
            $table->time('start_time_afternoon')->nullable();
            $table->time('end_time_afternoon')->nullable();
            $table->time('actual_start_time_morning')->nullable();
            $table->time('actual_end_time_morning')->nullable();
            $table->time('actual_start_time_afternoon')->nullable();
            $table->time('actual_end_time_afternoon')->nullable();
            $table->tinyInteger('status_id')->nullable();
            $table->unsignedBigInteger('custom_type_id')->nullable()->after('status_id');
            $table->foreignId('demande_conge_id')->nullable()->constrained('demande_conge')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plannings');
    }
};
