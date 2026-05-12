<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_answer_id',
        'teacher_id',
        'points_awarded',
        'feedback',
        'reviewed_at',
    ];

    protected $casts = [
        'points_awarded' => 'decimal:2',
        'reviewed_at'    => 'datetime',
    ];

    public function studentAnswer()
    {
        return $this->belongsTo(StudentAnswer::class, 'student_answer_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}

