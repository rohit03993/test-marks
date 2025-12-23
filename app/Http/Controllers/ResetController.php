<?php

namespace App\Http\Controllers;

use App\Models\ExamSubjectMark;
use App\Models\ExamResult;
use App\Models\ClassStudent;
use App\Models\Exam;
use App\Models\AcademicClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResetController extends Controller
{
    /**
     * Show reset confirmation page
     */
    public function index()
    {
        // Get counts for display
        $counts = [
            'students' => Student::count(),
            'classes' => AcademicClass::count(),
            'exams' => Exam::count(),
            'class_students' => ClassStudent::count(),
            'exam_results' => ExamResult::count(),
            'exam_subject_marks' => ExamSubjectMark::count(),
        ];

        return Inertia::render('Admin/Reset', [
            'counts' => $counts,
        ]);
    }

    /**
     * Reset all data
     */
    public function reset(Request $request)
    {
        $request->validate([
            'confirm_text' => 'required|string|in:RESET ALL DATA',
            'password' => 'required|string',
        ]);

        // Verify password (check against authenticated user's password)
        if (!\Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'Invalid password. Please enter your current password.']);
        }

        DB::beginTransaction();
        try {
            // Disable foreign key checks temporarily for truncate operations
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Delete in order to respect foreign key constraints
            
            // 1. Delete exam subject marks (depends on exam_results)
            ExamSubjectMark::truncate();
            
            // 2. Delete exam results (depends on exams and class_student)
            ExamResult::truncate();
            
            // 3. Delete class_exam pivot table entries
            DB::table('class_exam')->truncate();
            
            // 4. Delete class_student assignments (depends on students and classes)
            ClassStudent::truncate();
            
            // 5. Delete exams (no dependencies after removing class_id)
            Exam::truncate();
            
            // 6. Delete classes (no dependencies)
            AcademicClass::truncate();
            
            // 7. Delete students (no dependencies)
            Student::truncate();
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            // Note: Subjects are not deleted as they are seed data
            
            DB::commit();

            return redirect()->route('reset.index')
                ->with('success', 'All data has been reset successfully. The system is now empty and ready for new data.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Reset failed: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Reset failed: ' . $e->getMessage()]);
        }
    }
}
