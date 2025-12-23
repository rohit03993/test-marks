<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ClassStudent;

class StudentSearchService
{
    /**
     * Search students by roll number and class
     */
    public function searchByRollNumberAndClass(string $rollNumber, int $classId): ?Student
    {
        $classStudent = ClassStudent::where('class_id', $classId)
            ->where('roll_number', $rollNumber)
            ->where('is_active', true)
            ->with('student')
            ->first();

        return $classStudent ? $classStudent->student : null;
    }

    /**
     * Search students by name
     */
    public function searchByName(string $name): \Illuminate\Database\Eloquent\Collection
    {
        return Student::where('name', 'like', "%{$name}%")
            ->with(['activeClassStudent.academicClass'])
            ->limit(50)
            ->get();
    }

    /**
     * Get student profile with exam history
     */
    public function getStudentProfile(int $studentId): ?Student
    {
        return Student::with([
            'activeClassStudent.academicClass',
            'classStudents.academicClass',
            'classStudents.examResults.exam',
            'classStudents.examResults.subjectMarks.subject',
        ])
        ->find($studentId);
    }
}

