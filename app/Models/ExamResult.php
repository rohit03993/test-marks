<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'class_student_id',
        'total',
        'average',
        'status',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'average' => 'decimal:2',
    ];

    /**
     * Get the exam
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    /**
     * Get the class-student
     */
    public function classStudent(): BelongsTo
    {
        return $this->belongsTo(ClassStudent::class, 'class_student_id');
    }

    /**
     * Get all subject marks for this exam result
     */
    public function subjectMarks(): HasMany
    {
        return $this->hasMany(ExamSubjectMark::class, 'exam_result_id');
    }

    /**
     * Calculate and update total and average
     */
    public function calculateTotals(): void
    {
        $marks = $this->subjectMarks()
            ->whereNotNull('marks')
            ->pluck('marks')
            ->toArray();

        if (count($marks) > 0) {
            $this->total = array_sum($marks);
            $this->average = $this->total / count($marks);
        } else {
            $this->total = 0;
            $this->average = 0;
        }

        $this->save();
    }
}
