<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'exam_date',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    /**
     * Get the classes this exam belongs to (many-to-many)
     */
    public function academicClasses(): BelongsToMany
    {
        return $this->belongsToMany(AcademicClass::class, 'class_exam', 'exam_id', 'class_id')
            ->withTimestamps();
    }

    /**
     * Get all exam results for this exam
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'exam_id');
    }

    /**
     * Get the subjects for this exam (many-to-many)
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'exam_subject', 'exam_id', 'subject_id')
            ->withTimestamps();
    }
}
