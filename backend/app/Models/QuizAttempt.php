<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuizAttempt extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_IN_PROGRESS  = 'in_progress';
    const STATUS_SUBMITTED    = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_REVIEWED     = 'reviewed';

    protected $fillable = [
        'student_id',
        'quiz_id',
        'score',
        'total_points',
        'status',
        'started_at',
        'completed_at',
        'reviewed_at',
        'reviewed_by',
        'teacher_feedback',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class, 'attempt_id');
    }
}
