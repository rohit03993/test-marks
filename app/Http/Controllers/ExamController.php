<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Exam;
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

        return Inertia::render('Exams/Create', [
            'classes' => $classes,
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
        ]);

        $exam = Exam::create([
            'name' => $request->name,
            'exam_date' => $request->exam_date,
        ]);

        // Attach multiple classes
        $exam->academicClasses()->attach($request->class_ids);

        return redirect()->route('exams.index')
            ->with('success', 'Exam created successfully.');
    }

    /**
     * Show edit exam form
     */
    public function edit(Exam $exam)
    {
        $exam->load('academicClasses');
        $classes = AcademicClass::orderBy('name')->get();

        return Inertia::render('Exams/Create', [
            'examItem' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'exam_date' => $exam->exam_date->format('Y-m-d'),
                'class_ids' => $exam->academicClasses->pluck('id')->toArray(),
            ],
            'classes' => $classes,
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
        ]);

        $exam->update([
            'name' => $request->name,
            'exam_date' => $request->exam_date,
        ]);

        // Sync classes (replace all with new selection)
        $exam->academicClasses()->sync($request->class_ids);

        return redirect()->route('exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    /**
     * Delete exam
     */
    public function destroy(Exam $exam)
    {
        // Check if exam has results
        if ($exam->examResults()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete exam with results.']);
        }

        $exam->delete();

        return redirect()->route('exams.index')
            ->with('success', 'Exam deleted successfully.');
    }
}
