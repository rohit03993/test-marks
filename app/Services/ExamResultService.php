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
     * Process exam result upload
     */
    public function processExamResults(Exam $exam, int $classId, array $rows, array $columnMapping): array
    {
        $results = [
            'processed' => 0,
            'absent_marked' => 0,
            'unmapped' => [],
            'errors' => [],
        ];
        // Get subjects with lowercase keys for easier lookup
        $subjects = [];
        foreach (Subject::all() as $subject) {
            $subjects[strtolower($subject->name)] = $subject->id;
            $subjects[$subject->name] = $subject->id; // Also store original case
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

                    // Process subject marks
                    $marks = [];
                    $totalMarks = 0;
                    $subjectCount = 0;

                    // Map lowercase subject names to proper subject IDs
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

                    // Create absent marks for all subjects
                    foreach (['Physics', 'Chemistry', 'Mathematics'] as $subjectName) {
                        $subjectId = $subjects[$subjectName] ?? null;
                        if ($subjectId) {
                            ExamSubjectMark::create([
                                'exam_result_id' => $absentResult->id,
                                'subject_id' => $subjectId,
                                'marks' => null, // null = absent
                            ]);
                        }
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

