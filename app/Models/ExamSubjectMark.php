<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSubjectMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_result_id',
        'subject_id',
        'marks',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
    ];

    /**
     * Get the exam result
     */
    public function examResult(): BelongsTo
    {
        return $this->belongsTo(ExamResult::class, 'exam_result_id');
    }

    /**
     * Get the subject
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
