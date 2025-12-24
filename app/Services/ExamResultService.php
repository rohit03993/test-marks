<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubjectMark;
use App\Models\ClassStudent;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class ExamResultService
{
    /**
     * Process exam result upload with dynamic subject detection
     */
    public function processExamResults(Exam $exam, int $classId, array $rows, array $columnMapping): array
    {
        $results = [
            'processed' => 0,
            'absent_marked' => 0,
            'unmapped' => [],
            'errors' => [],
        ];

        // Load exam's subjects
        $exam->load('subjects');
        $examSubjects = $exam->subjects;
        
        if ($examSubjects->isEmpty()) {
            throw new \Exception('Exam must have at least one subject assigned.');
        }

        // Build subject lookup map (case-insensitive matching)
        $subjectMap = [];
        foreach ($examSubjects as $subject) {
            $nameLower = strtolower(trim($subject->name));
            $subjectMap[$nameLower] = [
                'id' => $subject->id,
                'name' => $subject->name,
            ];
            // Also add variations (e.g., "Math" for "Mathematics")
            if (strpos($nameLower, 'math') !== false) {
                $subjectMap['math'] = $subjectMap[$nameLower];
                $subjectMap['mathematics'] = $subjectMap[$nameLower];
            }
        }

        // Detect subject columns from column mapping (exclude roll_number)
        // Column mapping structure: ['excel_header' => 'field_name']
        // e.g., ['physics' => 'physics', 'roll number' => 'roll_number']
        $detectedSubjects = [];
        foreach ($columnMapping as $excelColumn => $fieldName) {
            if ($fieldName === 'roll_number') {
                continue;
            }
            
            // The fieldName should be the subject name in lowercase (e.g., 'physics', 'chemistry')
            // Try to find matching subject
            $fieldNameLower = strtolower(trim($fieldName));
            $excelColumnLower = strtolower(trim($excelColumn));
            $matchedSubject = null;
            
            // First, try to match by fieldName (the form field name, which is subject name in lowercase)
            if (isset($subjectMap[$fieldNameLower])) {
                $matchedSubject = $subjectMap[$fieldNameLower];
            }
            // If not found, try to match by Excel column header
            else if (isset($subjectMap[$excelColumnLower])) {
                $matchedSubject = $subjectMap[$excelColumnLower];
            }
            // Fuzzy match - check if column contains subject name or vice versa
            else {
                foreach ($subjectMap as $subjectKey => $subjectData) {
                    // Check if fieldName or excelColumn matches subject
                    if (strpos($fieldNameLower, $subjectKey) !== false || 
                        strpos($subjectKey, $fieldNameLower) !== false ||
                        strpos($excelColumnLower, $subjectKey) !== false || 
                        strpos($subjectKey, $excelColumnLower) !== false) {
                        $matchedSubject = $subjectData;
                        break;
                    }
                }
            }
            
            if ($matchedSubject) {
                // Store with fieldName as key (this is what we'll use to access row data)
                $detectedSubjects[$fieldName] = $matchedSubject;
            }
        }

        if (empty($detectedSubjects)) {
            throw new \Exception('No subject columns detected in the uploaded file. Please ensure column headers match exam subjects.');
        }

        DB::beginTransaction();
        try {
            $processedStudentIds = [];

            // Process each row from Excel
            foreach ($rows as $index => $row) {
                try {
                    $rollNumber = trim($row['roll_number'] ?? '');
                    
                    if (empty($rollNumber)) {
                        continue;
                    }

                    // Find class_student by roll number in this class
                    $classStudent = ClassStudent::where('class_id', $classId)
                        ->where('roll_number', $rollNumber)
                        ->where('is_active', true)
                        ->first();

                    if (!$classStudent) {
                        $results['unmapped'][] = [
                            'row' => $index + 1,
                            'roll_number' => $rollNumber,
                            'data' => $row,
                        ];
                        continue;
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

                    // Process subject marks dynamically
                    $marks = [];
                    $totalMarks = 0;
                    $subjectCount = 0;

                    $hasAnyMarks = false; // Track if student has at least one mark (even if 0 or negative)
                    
                    foreach ($detectedSubjects as $fieldName => $subjectData) {
                        $subjectId = $subjectData['id'];
                        $subjectName = $subjectData['name'];
                        
                        // Get mark value from row using field name
                        // Row data from extractDataRows uses field_name as keys
                        $markValue = $row[$fieldName] ?? null;
                        
                        // Convert to numeric, null if empty or blank
                        // 0, -1, -2 are valid marks, only null/blank means absent
                        if ($markValue !== null && $markValue !== '') {
                            $trimmedValue = trim((string) $markValue);
                            if ($trimmedValue === '' || $trimmedValue === null) {
                                $markValue = null; // Blank = absent
                            } else if (is_numeric($trimmedValue)) {
                                $markValue = (float) $trimmedValue; // 0, -1, -2, etc. are valid marks
                                $hasAnyMarks = true; // Student has at least one mark (even if 0 or negative)
                            } else {
                                $markValue = null; // Non-numeric = absent
                            }
                        } else {
                            $markValue = null; // Empty = absent
                        }

                        // Create or update subject mark
                        // Store null for absent, store 0/-1/-2/etc. for valid marks
                        ExamSubjectMark::updateOrCreate(
                            [
                                'exam_result_id' => $examResult->id,
                                'subject_id' => $subjectId,
                            ],
                            [
                                'marks' => $markValue, // Can be null, 0, negative, or positive
                            ]
                        );

                        // Include in calculation if not null (0 and negatives are included)
                        if ($markValue !== null) {
                            $marks[] = $markValue;
                            $totalMarks += $markValue;
                            $subjectCount++;
                        }
                    }

                    // Calculate total and average (includes 0 and negative marks)
                    $total = $totalMarks;
                    $average = $subjectCount > 0 ? $totalMarks / $subjectCount : 0;

                    // Determine status:
                    // - If student is in Excel and has at least one mark (even 0 or negative) = present
                    // - If student is in Excel but all marks are null/blank = absent
                    // - If student is not in Excel = absent (handled separately below)
                    $status = $hasAnyMarks ? 'present' : 'absent';

                    $examResult->update([
                        'total' => $total,
                        'average' => round($average, 2),
                        'status' => $status,
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

            // Auto-mark absent students (in class but not in Excel)
            $allClassStudents = ClassStudent::where('class_id', $classId)
                ->where('is_active', true)
                ->pluck('id');

            $absentStudents = $allClassStudents->diff($processedStudentIds);

            foreach ($absentStudents as $classStudentId) {
                $existingResult = ExamResult::where('exam_id', $exam->id)
                    ->where('class_student_id', $classStudentId)
                    ->first();

                if (!$existingResult) {
                    $absentResult = ExamResult::create([
                        'exam_id' => $exam->id,
                        'class_student_id' => $classStudentId,
                        'status' => 'absent',
                        'total' => 0,
                        'average' => 0,
                    ]);

                    // Create absent marks for all exam subjects
                    foreach ($examSubjects as $subject) {
                        ExamSubjectMark::create([
                            'exam_result_id' => $absentResult->id,
                            'subject_id' => $subject->id,
                            'marks' => null, // null = absent
                        ]);
                    }

                    $results['absent_marked']++;
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

