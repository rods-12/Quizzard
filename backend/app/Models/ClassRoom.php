<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'teacher_id',
        'name',
        'description',
        'class_code',
        'grading_mode',
    ];

    protected static function booted()
    {
        static::creating(function ($class) {
            if (empty($class->class_code)) {
                do {
                    $code = strtoupper(Str::random(6));
                } while (self::where('class_code', $code)->exists());
                $class->class_code = $code;
            }
        });
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'class_students', 'class_id', 'student_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'class_quizzes', 'class_id', 'quiz_id')
            ->withPivot('assigned_at', 'due_date', 'grading_mode')
            ->withTimestamps();
    }
}
