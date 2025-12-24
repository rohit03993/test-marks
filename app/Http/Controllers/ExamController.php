<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Exam;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamController extends Controller
{
    /**
     * Display list of exams
     */
    public function index(Request $request)
    {
        $exams = Exam::query()
            ->with(['academicClasses'])
            ->withCount('examResults')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->class_id, function ($query, $classId) {
                $query->whereHas('academicClasses', function ($q) use ($classId) {
                    $q->where('classes.id', $classId);
                });
            })
            ->latest('exam_date')
            ->paginate(20);

        $classes = AcademicClass::orderBy('name')->get();

        return Inertia::render('Exams/Index', [
            'exams' => $exams,
            'classes' => $classes,
            'filters' => $request->only(['search', 'class_id']),
        ]);
    }

    /**
     * Show create exam form
     */
    public function create()
    {
        $classes = AcademicClass::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();

        return Inertia::render('Exams/Create', [
            'classes' => $classes,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Store new exam
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'exam_date' => 'required|date',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $exam = Exam::create([
            'name' => $request->name,
            'exam_date' => $request->exam_date,
        ]);

        // Sync classes (remove duplicates and attach)
        $exam->academicClasses()->sync(array_unique($request->class_ids));

        // Sync subjects (remove duplicates and attach)
        // Using sync() instead of attach() prevents duplicate entry errors
        $exam->subjects()->sync(array_unique($request->subject_ids));

        return redirect()->route('exams.index')
            ->with('success', 'Exam created successfully.');
    }

    /**
     * Show edit exam form
     */
    public function edit(Exam $exam)
    {
        $exam->load(['academicClasses', 'subjects']);
        $classes = AcademicClass::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();

        return Inertia::render('Exams/Create', [
            'examItem' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'exam_date' => $exam->exam_date->format('Y-m-d'),
                'class_ids' => $exam->academicClasses->pluck('id')->toArray(),
                'subject_ids' => $exam->subjects->pluck('id')->toArray(),
            ],
            'classes' => $classes,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Update exam
     */
    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'exam_date' => 'required|date',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $exam->update([
            'name' => $request->name,
            'exam_date' => $request->exam_date,
        ]);

        // Sync classes (replace all with new selection, remove duplicates)
        $exam->academicClasses()->sync(array_unique($request->class_ids));

        // Sync subjects (replace all with new selection, remove duplicates)
        $exam->subjects()->sync(array_unique($request->subject_ids));

        return redirect()->route('exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    /**
     * Delete exam (cascades to all results and subject marks)
     */
    public function destroy(Exam $exam)
    {
        // Delete exam - cascade deletes will handle exam_results and exam_subject_marks
        // Foreign keys should be set to CASCADE in migrations
        $exam->delete();

        return redirect()->route('exams.index')
            ->with('success', 'Exam and all associated results have been deleted successfully.');
    }

    /**
     * View all students who took this exam
     */
    public function viewResults(Exam $exam)
    {
        $exam->load(['academicClasses', 'subjects']);
        
        // Get all exam results with student information
        $examResults = \App\Models\ExamResult::where('exam_id', $exam->id)
            ->with([
                'classStudent.student',
                'classStudent.academicClass',
                'subjectMarks.subject',
            ])
            ->orderBy('total', 'desc')
            ->get();

        $students = $examResults->map(function ($result) use ($exam) {
            $subjectMarks = [];
            foreach ($result->subjectMarks as $subjectMark) {
                if ($subjectMark->subject) {
                    $subjectMarks[$subjectMark->subject->name] = $subjectMark->marks;
                }
            }

            return [
                'student_id' => $result->classStudent->student->id,
                'name' => $result->classStudent->student->name,
                'roll_number' => $result->classStudent->roll_number,
                'class_name' => $result->classStudent->academicClass->name ?? 'Unknown',
                'total' => $result->total,
                'average' => $result->average,
                'status' => $result->status,
                'subject_marks' => $subjectMarks,
            ];
        });

        return Inertia::render('Exams/ViewResults', [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'exam_date' => $exam->exam_date->format('Y-m-d'),
                'classes' => $exam->academicClasses->pluck('name')->join(', '),
                'subjects' => $exam->subjects->pluck('name'),
            ],
            'students' => $students,
        ]);
    }

    /**
     * Create a new subject
     */
    public function createSubject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:subjects,name',
        ]);

        $subject = Subject::create([
            'name' => trim($request->name),
        ]);

        return response()->json([
            'success' => true,
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
            ],
        ]);
    }
}
