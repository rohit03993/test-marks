<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassStudent extends Model
{
    use HasFactory;

    protected $table = 'class_student';

    protected $fillable = [
        'student_id',
        'class_id',
        'roll_number',
        'is_active',
        'joined_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'date',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the class
     */
    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    /**
     * Get all exam results for this class-student
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'class_student_id');
    }
}
