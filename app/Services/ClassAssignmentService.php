<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\ClassStudent;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ClassAssignmentService
{
    /**
     * Bulk assign students to a class
     */
    public function bulkAssignStudents(array $studentIds, int $classId, array $rollNumbers = []): array
    {
        $results = [
            'assigned' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        $class = AcademicClass::findOrFail($classId);

        DB::beginTransaction();
        try {
            foreach ($studentIds as $index => $studentId) {
                try {
                    $student = Student::findOrFail($studentId);
                    $rollNumber = $rollNumbers[$index] ?? $student->roll_number ?? null;

                    if (!$rollNumber) {
                        $results['errors'][] = [
                            'student_id' => $studentId,
                            'student_name' => $student->name,
                            'error' => 'Roll number is required',
                        ];
                        continue;
                    }

                    // Check if roll number already exists in this class
                    $existingRoll = ClassStudent::where('class_id', $classId)
                        ->where('roll_number', $rollNumber)
                        ->where('student_id', '!=', $studentId)
                        ->first();

                    if ($existingRoll) {
                        $results['errors'][] = [
                            'student_id' => $studentId,
                            'student_name' => $student->name,
                            'roll_number' => $rollNumber,
                            'error' => "Roll number {$rollNumber} already exists in this class",
                        ];
                        continue;
                    }

                    // Deactivate previous active class assignment
                    ClassStudent::where('student_id', $studentId)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    // Check if student already has assignment in this class
                    $existingAssignment = ClassStudent::where('student_id', $studentId)
                        ->where('class_id', $classId)
                        ->first();

                    if ($existingAssignment) {
                        // Update existing assignment
                        $existingAssignment->update([
                            'roll_number' => $rollNumber,
                            'is_active' => true,
                            'joined_at' => $existingAssignment->joined_at ?? now(),
                        ]);
                        $results['updated']++;
                    } else {
                        // Create new assignment
                        ClassStudent::create([
                            'student_id' => $studentId,
                            'class_id' => $classId,
                            'roll_number' => $rollNumber,
                            'is_active' => true,
                            'joined_at' => now(),
                        ]);
                        $results['assigned']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'student_id' => $studentId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get students available for assignment (not in any class or can be moved)
     */
    public function getAvailableStudents(array $excludeStudentIds = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Student::query();

        if (!empty($excludeStudentIds)) {
            $query->whereNotIn('id', $excludeStudentIds);
        }

        return $query->orderBy('name')->get();
    }
}

