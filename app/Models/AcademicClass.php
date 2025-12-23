<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
    ];

    /**
     * Get all class-student assignments for this class
     */
    public function classStudents(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'class_id');
    }

    /**
     * Get all exams for this class (many-to-many)
     */
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'class_exam', 'class_id', 'exam_id')
            ->withTimestamps();
    }

    /**
     * Get active students in this class
     */
    public function activeStudents()
    {
        return $this->classStudents()->where('is_active', true);
    }
}

