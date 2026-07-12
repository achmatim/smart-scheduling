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
        Schema::create('scheduling_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('progress')->default(0); // 0 - 100
            $table->integer('max_generations')->default(100);
            $table->integer('current_generation')->default(0);
            $table->double('best_fitness')->default(0.0);
            $table->integer('conflicts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduling_jobs');
    }
};
