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
        Schema::create('teacher_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->integer('day_of_week'); // 1 = Senin, ..., 5 = Jumat
            $table->integer('period_number'); // 1 = Jam ke-1, ..., 8 = Jam ke-8
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            // Unique constraint to prevent duplicate entry for a teacher on same day & period
            $table->unique(['teacher_id', 'day_of_week', 'period_number'], 'teacher_avail_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_availabilities');
    }
};
