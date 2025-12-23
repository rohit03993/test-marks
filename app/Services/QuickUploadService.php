<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubjectMark;
use App\Models\ClassStudent;
use App\Models\Student;
use App\Models\AcademicClass;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class QuickUploadService
{
    /**
     * Process quick exam result upload (without class requirement)
     * Matches students by roll_number from students table
     */
    public function processQuickUpload(string $examName, string $examDate, array $rows, array $columnMapping): array
    {
        $results = [
            'processed' => 0,
            'unmapped' => [],
            'errors' => [],
            'exam_id' => null,
        ];

        // Get or create a default "General" class for quick uploads
        $defaultClass = AcademicClass::firstOrCreate(
            ['name' => 'General'],
            ['name' => 'General']
        );

        // Get subjects
        $subjects = [];
        foreach (Subject::all() as $subject) {
            $subjects[strtolower($subject->name)] = $subject->id;
            $subjects[$subject->name] = $subject->id;
        }

        DB::beginTransaction();
        try {
            // Create exam without class (or attach to default class)
            $exam = Exam::create([
                'name' => $examName,
                'exam_date' => $examDate,
            ]);

            // Attach to default class
            $exam->academicClasses()->attach($defaultClass->id);

            $results['exam_id'] = $exam->id;
            $processedStudentIds = [];

            // Process each row from Excel
            foreach ($rows as $index => $row) {
                try {
                    // Get roll number and clean it thoroughly
                    $rollNumberRaw = $row['roll_number'] ?? '';
                    
                    // Convert to string, trim whitespace, and remove any non-printable characters
                    $rollNumber = trim((string) $rollNumberRaw);
                    $rollNumber = preg_replace('/\s+/', '', $rollNumber); // Remove all whitespace
                    
                    // If empty after cleaning, skip
                    if (empty($rollNumber)) {
                        continue;
                    }
                    
                    // Also try with the original value (in case it's stored as number in Excel)
                    $rollNumberAlt = trim((string) $rollNumberRaw);
                    
                    // Find student by roll_number - try multiple variations
                    $student = null;
                    
                    // Try exact match (cleaned)
                    $student = Student::where('roll_number', $rollNumber)->first();
                    
                    // Try with trimmed original (handles text vs number)
                    if (!$student) {
                        $student = Student::where('roll_number', $rollNumberAlt)->first();
                    }
                    
                    // Try with LIKE for partial matches (handles leading zeros or formatting)
                    if (!$student) {
                        $student = Student::where('roll_number', 'like', "%{$rollNumber}%")->first();
                    }
                    
                    // If not found in students table, check class_student table
                    if (!$student) {
                        $classStudent = ClassStudent::where('roll_number', $rollNumber)
                            ->orWhere('roll_number', $rollNumberAlt)
                            ->orWhere('roll_number', 'like', "%{$rollNumber}%")
                            ->where('is_active', true)
                            ->with('student')
                            ->first();
                        
                        if ($classStudent && $classStudent->student) {
                            $student = $classStudent->student;
                            // Update student's roll_number in students table for future lookups
                            if (!$student->roll_number) {
                                $student->update(['roll_number' => $rollNumber]);
                            }
                        }
                    }
                    
                    // If still not found, try searching by name as fallback (if name column exists)
                    if (!$student && isset($row['name'])) {
                        $name = trim($row['name']);
                        if (!empty($name)) {
                            $student = Student::where('name', 'like', "%{$name}%")->first();
                        }
                    }

                    if (!$student) {
                        $results['unmapped'][] = [
                            'row' => $index + 1,
                            'roll_number' => $rollNumber,
                            'roll_number_original' => $rollNumberRaw,
                            'data' => $row,
                        ];
                        continue;
                    }

                    // For quick uploads, always use/create a class_student in the "General" class
                    // This ensures results are always linked to the General class for quick uploads
                    $classStudent = ClassStudent::where('student_id', $student->id)
                        ->where('class_id', $defaultClass->id)
                        ->first(); // Don't filter by is_active - get any entry in General class

                    if (!$classStudent) {
                        // Create new class_student entry in General class with roll_number
                        // Deactivate any other active class assignments for this student
                        ClassStudent::where('student_id', $student->id)
                            ->where('is_active', true)
                            ->update(['is_active' => false]);

                        $classStudent = ClassStudent::create([
                            'student_id' => $student->id,
                            'class_id' => $defaultClass->id,
                            'roll_number' => $rollNumber,
                            'is_active' => true,
                            'joined_at' => now(),
                        ]);
                    } else {
                        // Update roll number and ensure it's active
                        $classStudent->update([
                            'roll_number' => $rollNumber,
                            'is_active' => true,
                        ]);
                        
                        // Deactivate any other active class assignments
                        ClassStudent::where('student_id', $student->id)
                            ->where('id', '!=', $classStudent->id)
                            ->where('is_active', true)
                            ->update(['is_active' => false]);
                    }

                    // Check if result already exists
                    $examResult = ExamResult::where('exam_id', $exam->id)
                        ->where('class_student_id', $classStudent->id)
                        ->first();

                    if (!$examResult) {
                        $examResult = ExamResult::create([
                            'exam_id' => $exam->id,
                            'class_student_id' => $classStudent->id,
                            'status' => 'present',
                            'total' => 0,
                            'average' => 0,
                        ]);
                    }

                    // Process subject marks
                    $marks = [];
                    $totalMarks = 0;
                    $subjectCount = 0;

                    $subjectMap = [
                        'physics' => $subjects['physics'] ?? $subjects['Physics'] ?? null,
                        'chemistry' => $subjects['chemistry'] ?? $subjects['Chemistry'] ?? null,
                        'mathematics' => $subjects['mathematics'] ?? $subjects['Mathematics'] ?? null,
                    ];

                    foreach (['physics', 'chemistry', 'mathematics'] as $subjectName) {
                        $subjectId = $subjectMap[$subjectName] ?? null;
                        if (!$subjectId) {
                            continue;
                        }

                        $markValue = $row[$subjectName] ?? null;
                        
                        // Convert to numeric, null if empty
                        if ($markValue !== null && $markValue !== '') {
                            $markValue = is_numeric($markValue) ? (float) $markValue : null;
                        } else {
                            $markValue = null;
                        }

                        // Validate marks range (0-100)
                        if ($markValue !== null && ($markValue < 0 || $markValue > 100)) {
                            $results['errors'][] = [
                                'row' => $index + 1,
                                'roll_number' => $rollNumber,
                                'error' => "Invalid marks for {$subjectName}: {$markValue}",
                            ];
                            continue;
                        }

                        // Create or update subject mark
                        ExamSubjectMark::updateOrCreate(
                            [
                                'exam_result_id' => $examResult->id,
                                'subject_id' => $subjectId,
                            ],
                            [
                                'marks' => $markValue,
                            ]
                        );

                        if ($markValue !== null) {
                            $marks[] = $markValue;
                            $totalMarks += $markValue;
                            $subjectCount++;
                        }
                    }

                    // Calculate total and average
                    $total = $totalMarks;
                    $average = $subjectCount > 0 ? $totalMarks / $subjectCount : 0;

                    $examResult->update([
                        'total' => $total,
                        'average' => round($average, 2),
                        'status' => 'present',
                    ]);

                    $processedStudentIds[] = $classStudent->id;
                    $results['processed']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'data' => $row,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
            
            // Log summary for debugging
            \Log::info('Quick Upload Summary', [
                'exam_id' => $results['exam_id'],
                'exam_name' => $examName,
                'processed' => $results['processed'],
                'unmapped_count' => count($results['unmapped']),
                'errors_count' => count($results['errors']),
            ]);
            
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

