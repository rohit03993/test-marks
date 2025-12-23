<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'father_name',
        'roll_number',
    ];

    /**
     * Get all class-student assignments for this student
     */
    public function classStudents(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'student_id');
    }

    /**
     * Get the current active class assignment
     */
    public function activeClassStudent()
    {
        return $this->hasOne(ClassStudent::class, 'student_id')
            ->where('is_active', true);
    }

    /**
     * Get current class
     */
    public function currentClass()
    {
        return $this->hasOneThrough(
            AcademicClass::class,
            ClassStudent::class,
            'student_id',
            'id',
            'id',
            'class_id'
        )->where('class_student.is_active', true);
    }
}
