<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_quizzes', function (Blueprint $table) {
            $table->enum('grading_mode', ['automatic', 'manual'])
                  ->default('automatic')
                  ->after('assigned_at');
        });
    }

    public function down(): void
    {
        Schema::table('class_quizzes', function (Blueprint $table) {
            $table->dropColumn('grading_mode');
        });
    }
};
