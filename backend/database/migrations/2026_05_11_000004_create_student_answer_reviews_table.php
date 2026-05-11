<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_answer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_answer_id')
                  ->constrained('student_answers')
                  ->onDelete('cascade');
            $table->foreignId('teacher_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->decimal('points_awarded', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_answer_reviews');
    }
};
