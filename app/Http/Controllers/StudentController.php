<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStudentUpload;
use App\Models\Student;
use App\Services\ExcelImportService;
use App\Services\StudentSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StudentController extends Controller
{
    protected $excelService;
    protected $searchService;

    public function __construct(ExcelImportService $excelService, StudentSearchService $searchService)
    {
        $this->excelService = $excelService;
        $this->searchService = $searchService;
    }

    /**
     * Display list of students
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 20;
        
        // Handle "all" option
        if ($perPage === 'all') {
            $perPage = 999999; // Large number to get all records
        } else {
            $perPage = (int) $perPage;
        }

        $query = Student::query()
            ->with(['activeClassStudent.academicClass'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%");
            })
            ->when($request->class_id === 'unassigned', function ($query) {
                // Filter for students without active class assignment
                $query->whereDoesntHave('activeClassStudent', function ($q) {
                    $q->where('is_active', true);
                });
            })
            ->when($request->class_id && $request->class_id !== 'unassigned', function ($query, $classId) {
                $query->whereHas('activeClassStudent', function ($q) use ($classId) {
                    $q->where('class_id', $classId);
                });
            })
            ->latest();

        // If per_page is 'all', get all results without pagination
        if ($perPage === 999999) {
            $students = $query->get();
            // Convert to paginator-like structure for frontend compatibility
            $students = new \Illuminate\Pagination\LengthAwarePaginator(
                $students,
                $students->count(),
                $students->count(),
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $students = $query->paginate($perPage);
        }

        $classes = \App\Models\AcademicClass::orderBy('name')->get();

        return Inertia::render('Students/Index', [
            'students' => $students,
            'classes' => $classes,
            'filters' => $request->only(['search', 'class_id', 'per_page']),
        ]);
    }

    /**
     * Show upload page
     */
    public function showUpload()
    {
        return Inertia::render('Students/Upload');
    }

    /**
     * Handle student master upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'column_mapping' => 'required|array',
            'column_mapping.name' => 'required|string',
            'column_mapping.father_name' => 'nullable|string',
            'column_mapping.roll_number' => 'nullable|string',
        ]);

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
            ProcessStudentUpload::dispatch($filePath, $columnMapping);

            return redirect()->route('students.index')
                ->with('success', 'Student upload started. Results will be available shortly.');
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
            
            // Ensure file is valid
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
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error reading file headers: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Show search page
     */
    public function search()
    {
        $classes = \App\Models\AcademicClass::orderBy('name')->get();
        
        return Inertia::render('Search/Index', [
            'classes' => $classes,
        ]);
    }

    /**
     * Perform search
     */
    public function performSearch(Request $request)
    {
        $request->validate([
            'search_type' => 'required|in:name,roll_number',
            'name' => 'required_if:search_type,name|string',
            'roll_number' => 'required_if:search_type,roll_number|string',
            'class_id' => 'required_if:search_type,roll_number|exists:classes,id',
        ]);

        if ($request->search_type === 'name') {
            $students = $this->searchService->searchByName($request->name);
            
            if ($students->count() === 1) {
                // If only one result, redirect to profile
                return redirect()->route('students.profile', $students->first()->id);
            }
            
            return Inertia::render('Search/Index', [
                'classes' => \App\Models\AcademicClass::orderBy('name')->get(),
                'searchResults' => $students,
                'searchType' => 'name',
                'searchTerm' => $request->name,
            ]);
        } else {
            // Search by roll number and class
            $student = $this->searchService->searchByRollNumberAndClass(
                $request->roll_number,
                $request->class_id
            );

            if ($student) {
                return redirect()->route('students.profile', $student->id);
            }

            return back()->withErrors(['error' => 'Student not found with the provided roll number and class.']);
        }
    }

    /**
     * Show student profile
     */
    public function profile(Student $student)
    {
        $student = $this->searchService->getStudentProfile($student->id);

        if (!$student) {
            return redirect()->route('students.index')
                ->withErrors(['error' => 'Student not found.']);
        }

        // Format exam history - load all exam results for all class_students
        $examHistory = [];
        
        // Get all class_students for this student (including inactive ones)
        // Use direct query to ensure we get everything
        $classStudentIds = \App\Models\ClassStudent::where('student_id', $student->id)
            ->pluck('id')
            ->toArray();
        
        if (!empty($classStudentIds)) {
            // Get all exam results for all class_students of this student
            $examResults = \App\Models\ExamResult::whereIn('class_student_id', $classStudentIds)
                ->with([
                    'exam',
                    'classStudent.academicClass',
                    'subjectMarks.subject',
                ])
                ->orderBy('created_at', 'desc')
                ->get();
            
            foreach ($examResults as $examResult) {
                if (!$examResult->exam) {
                    continue; // Skip if exam is missing
                }
                
                $subjectMarks = [];
                foreach ($examResult->subjectMarks as $subjectMark) {
                    if ($subjectMark->subject) {
                        $subjectMarks[$subjectMark->subject->name] = $subjectMark->marks;
                    }
                }

                $examHistory[] = [
                    'exam_id' => $examResult->exam->id,
                    'exam_name' => $examResult->exam->name,
                    'exam_date' => $examResult->exam->exam_date,
                    'class_name' => $examResult->classStudent->academicClass->name ?? 'Unknown',
                    'roll_number' => $examResult->classStudent->roll_number ?? '',
                    'physics' => $subjectMarks['Physics'] ?? null,
                    'chemistry' => $subjectMarks['Chemistry'] ?? null,
                    'mathematics' => $subjectMarks['Mathematics'] ?? null,
                    'total' => $examResult->total ?? 0,
                    'average' => $examResult->average ?? 0,
                    'status' => $examResult->status ?? 'absent',
                ];
            }
        }

        // Sort by exam date (newest first)
        usort($examHistory, function ($a, $b) {
            $dateA = is_string($a['exam_date']) ? strtotime($a['exam_date']) : $a['exam_date']->timestamp;
            $dateB = is_string($b['exam_date']) ? strtotime($b['exam_date']) : $b['exam_date']->timestamp;
            return $dateB - $dateA;
        });

        return Inertia::render('Students/Profile', [
            'student' => $student,
            'examHistory' => $examHistory,
        ]);
    }
}
