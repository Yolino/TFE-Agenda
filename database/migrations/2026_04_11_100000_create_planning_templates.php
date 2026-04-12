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
        Schema::create('planning_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week');
            $table->time('start_time_morning')->nullable();
            $table->time('end_time_morning')->nullable();
            $table->time('start_time_afternoon')->nullable();
            $table->time('end_time_afternoon')->nullable();
            $table->tinyInteger('status_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'day_of_week']);
            $table->unique(['user_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_templates');
    }
};
