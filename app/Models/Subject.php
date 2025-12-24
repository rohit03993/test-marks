<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get all exam subject marks for this subject
     */
    public function examSubjectMarks(): HasMany
    {
        return $this->hasMany(ExamSubjectMark::class, 'subject_id');
    }

    /**
     * Get all exams that include this subject (many-to-many)
     */
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_subject', 'subject_id', 'exam_id')
            ->withTimestamps();
    }
}
