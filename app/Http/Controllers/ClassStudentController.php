<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Services\ClassAssignmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassStudentController extends Controller
{
    protected $assignmentService;

    public function __construct(ClassAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Show bulk assignment page
     */
    public function showBulkAssign(Request $request)
    {
        $studentIds = $request->get('student_ids', []);
        
        $students = \App\Models\Student::whereIn('id', $studentIds)->get();
        $classes = AcademicClass::orderBy('name')->get();

        return Inertia::render('Students/BulkAssign', [
            'students' => $students,
            'classes' => $classes,
            'selectedStudentIds' => $studentIds,
        ]);
    }

    /**
     * Process bulk assignment
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'roll_numbers' => 'nullable|array',
            'roll_numbers.*' => 'nullable|string|max:50',
        ]);

        try {
            $results = $this->assignmentService->bulkAssignStudents(
                $request->student_ids,
                $request->class_id,
                $request->roll_numbers ?? []
            );

            $message = "Successfully assigned {$results['assigned']} students";
            if ($results['updated'] > 0) {
                $message .= ", updated {$results['updated']} assignments";
            }
            if (count($results['errors']) > 0) {
                $message .= ", " . count($results['errors']) . " errors occurred";
            }

            return redirect()->route('students.index')
                ->with('success', $message)
                ->with('assignment_errors', $results['errors']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Assignment failed: ' . $e->getMessage()]);
        }
    }
}
