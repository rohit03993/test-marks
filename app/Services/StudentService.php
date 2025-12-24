<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentService
{
    /**
     * Create or update student from Excel data
     * STRICT RULE: Roll number is the primary identifier - no duplicates allowed
     */
    public function createOrUpdateStudent(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $fatherName = trim($data['father_name'] ?? '');
        $rollNumber = trim($data['roll_number'] ?? '');

        if (empty($name)) {
            return ['status' => 'error', 'message' => 'Student name is required'];
        }

        // STRICT RULE: If roll number exists, it's the primary identifier
        // Check for existing student by roll number FIRST
        $student = null;
        if (!empty($rollNumber)) {
            $student = Student::where('roll_number', $rollNumber)->first();
        }

        if ($student) {
            // Update existing student with latest information
            // Latest information always wins (fills missing data, updates existing)
            $student->name = $name; // Always update name with latest
            
            // Update father name if new one is provided (latest wins)
            if (!empty($fatherName)) {
                $student->father_name = $fatherName;
            }
            
            // Roll number already exists, so we keep it
            $student->save();

            return [
                'status' => 'updated',
                'student' => $student,
                'message' => 'Student updated (matched by roll number)'
            ];
        } else {
            // Check if roll number already exists (shouldn't happen due to unique constraint, but double-check)
            if (!empty($rollNumber)) {
                $existingByRoll = Student::where('roll_number', $rollNumber)->first();
                if ($existingByRoll) {
                    // This should not happen, but handle it gracefully
                    $existingByRoll->name = $name;
                    if (!empty($fatherName)) {
                        $existingByRoll->father_name = $fatherName;
                    }
                    $existingByRoll->save();
                    
                    return [
                        'status' => 'updated',
                        'student' => $existingByRoll,
                        'message' => 'Student updated (duplicate roll number prevented)'
                    ];
                }
            }
            
            // Create new student only if roll number doesn't exist
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

