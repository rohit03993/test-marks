<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
