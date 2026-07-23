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
        // 1. Create schools table
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // 2. Drop global unique constraints
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique('teachers_nip_unique');
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique('rooms_code_unique');
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_code_unique');
        });
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropUnique('rombels_name_unique');
        });

        // 3. Add school_id column to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
        });

        // 4. Add school_id column to other tables
        $tables = [
            'academic_years',
            'teachers',
            'rombels',
            'rooms',
            'subjects',
            'lessons',
            'schedules',
            'teacher_availabilities',
            'scheduling_jobs',
            'periods'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            });
        }

        // 5. Add new compound unique constraints scoped by school_id
        Schema::table('teachers', function (Blueprint $table) {
            $table->unique(['school_id', 'nip']);
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->unique(['school_id', 'code']);
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->unique(['school_id', 'code']);
        });
        Schema::table('rombels', function (Blueprint $table) {
            $table->unique(['school_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'name']);
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'code']);
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'code']);
        });
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'nip']);
        });

        $tables = [
            'academic_years',
            'teachers',
            'rombels',
            'rooms',
            'subjects',
            'lessons',
            'schedules',
            'teacher_availabilities',
            'scheduling_jobs',
            'periods'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });

        // Restore global unique constraints
        Schema::table('rombels', function (Blueprint $table) {
            $table->unique('name');
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->unique('code');
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->unique('code');
        });
        Schema::table('teachers', function (Blueprint $table) {
            $table->unique('nip');
        });

        Schema::dropIfExists('schools');
    }
};
