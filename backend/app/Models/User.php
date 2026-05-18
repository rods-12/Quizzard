<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'middle_initial',
        'surname',
        'email',
        'password',
        'role',
        'status',
        'failed_login_attempts',
        'locked_until',
        'profile_picture',
        'profile_image',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'locked_until'      => 'datetime',
        ];
    }

    public function getNameAttribute($value)
    {
        $first = trim((string) $this->first_name);
        $middle = trim((string) $this->middle_initial);
        $last = trim((string) $this->surname);

        if ($first !== '' || $last !== '') {
            $middleFormatted = $middle !== ''
                ? ' ' . strtoupper(substr($middle, 0, 1)) . '.'
                : '';

            return trim("{$first}{$middleFormatted} {$last}");
        }

        return $value;
    }

    public function getProfileImageUrlAttribute(): string
    {
        if ($this->profile_image) {
            return asset('storage/' . $this->profile_image);
        }
        return asset('images/default-avatar.png');
    }

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(\App\Models\QuizAttempt::class, 'student_id');
    }
 
    public function quizzes()
    {
        return $this->hasMany(\App\Models\Quiz::class, 'teacher_id');
    }
 
}