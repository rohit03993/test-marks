<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessExamResultUpload;
use App\Models\Exam;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExamResultController extends Controller
{
    protected $excelService;

    public function __construct(ExcelImportService $excelService)
    {
        $this->excelService = $excelService;
    }

    /**
     * Show upload results page
     */
    public function showUpload(Exam $exam)
    {
        $exam->load(['academicClasses', 'subjects']);
        
        return Inertia::render('Exams/UploadResults', [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'exam_date' => $exam->exam_date->format('Y-m-d'),
                'classes' => $exam->academicClasses->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                    ];
                }),
                'subjects' => $exam->subjects->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Handle exam result upload
     */
    public function upload(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'class_id' => 'required|exists:classes,id',
            'column_mapping' => 'required|array',
            'column_mapping.roll_number' => 'required|string',
            // Dynamic subject columns - no hardcoded validation
        ]);

        // Verify that the selected class belongs to this exam
        if (!$exam->academicClasses()->where('classes.id', $request->class_id)->exists()) {
            return back()->withErrors(['error' => 'Selected class does not belong to this exam.']);
        }

        // Verify exam has subjects
        $exam->load('subjects');
        if ($exam->subjects->isEmpty()) {
            return back()->withErrors(['error' => 'Exam must have at least one subject assigned.']);
        }

        try {
            // Store uploaded file
            $filePath = $request->file('file')->store('temp', 'local');
            
            // Normalize column mapping keys to lowercase
            $columnMapping = [];
            foreach ($request->column_mapping as $key => $value) {
                if ($value) {
                    $columnMapping[strtolower(trim($value))] = $key;
                }
            }

            // Dispatch job to process in background
            ProcessExamResultUpload::dispatch($exam->id, $request->class_id, $filePath, $columnMapping);

            return redirect()->route('exams.index')
                ->with('success', 'Exam results upload started. Results will be available shortly.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get Excel headers for column mapping
     */
    public function getHeaders(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $tempPath = null;
        try {
            $file = $request->file('file');
            
            if (!$file->isValid()) {
                return response()->json(['error' => 'Invalid file uploaded'], 422);
            }
            
            // Store file temporarily to get a valid path
            $tempPath = $file->store('temp', 'local');
            $fullPath = Storage::path($tempPath);
            
            $headers = $this->excelService->getHeaders($fullPath);
            
            // Clean up temp file
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            
            if (empty($headers)) {
                return response()->json(['error' => 'Could not read headers from file. Please ensure the file has a header row.'], 422);
            }
            
            return response()->json(['headers' => $headers]);
        } catch (\Exception $e) {
            // Clean up temp file on error
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            
            \Log::error('Error getting headers: ' . $e->getMessage());
            return response()->json(['error' => 'Error reading file headers: ' . $e->getMessage()], 422);
        }
    }
}
