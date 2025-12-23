<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentService
{
    /**
     * Create or update student from Excel data
     */
    public function createOrUpdateStudent(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $fatherName = trim($data['father_name'] ?? '');
        $rollNumber = trim($data['roll_number'] ?? '');

        if (empty($name)) {
            return ['status' => 'error', 'message' => 'Student name is required'];
        }

        // Try to find existing student by name and father name
        $student = Student::where('name', $name)
            ->when($fatherName, function ($query) use ($fatherName) {
                return $query->where('father_name', $fatherName);
            })
            ->first();

        if ($student) {
            // Update existing student
            if (empty($student->father_name) && $fatherName) {
                $student->father_name = $fatherName;
            }
            if (empty($student->roll_number) && $rollNumber) {
                $student->roll_number = $rollNumber;
            }
            $student->save();

            return [
                'status' => 'updated',
                'student' => $student,
                'message' => 'Student updated'
            ];
        } else {
            // Create new student
            $student = Student::create([
                'name' => $name,
                'father_name' => $fatherName ?: null,
                'roll_number' => $rollNumber ?: null,
            ]);

            return [
                'status' => 'created',
                'student' => $student,
                'message' => 'Student created'
            ];
        }
    }

    /**
     * Process bulk student upload
     */
    public function processBulkUpload(array $rows): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                try {
                    $result = $this->createOrUpdateStudent($row);
                    if ($result['status'] === 'created') {
                        $results['created']++;
                    } elseif ($result['status'] === 'updated') {
                        $results['updated']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'data' => $row,
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
}

