<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->enum('status', ['in_progress', 'submitted', 'under_review', 'reviewed'])
                  ->default('in_progress')
                  ->change();

            $table->timestamp('reviewed_at')->nullable()->after('completed_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                  ->constrained('users')->nullOnDelete();
            $table->text('teacher_feedback')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'teacher_feedback']);

            $table->enum('status', ['in_progress', 'completed'])
                  ->default('in_progress')
                  ->change();
        });
    }
};
