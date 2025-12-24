<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\ClassStudent;
use App\Models\Student;
use App\Exports\ClassReportExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ResultsController extends Controller
{
    /**
     * Show class selection page
     */
    public function index()
    {
        $classes = AcademicClass::orderBy('name')->get();

        return Inertia::render('Results/Index', [
            'classes' => $classes,
        ]);
    }

    /**
     * Show students in a class with their test counts
     */
    public function showClass(Request $request, AcademicClass $class)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:10|max:500',
        ]);

        $perPage = $request->per_page ?? 50;

        // Get all active students in this class
        $classStudents = ClassStudent::where('class_id', $class->id)
            ->where('is_active', true)
            ->with([
                'student',
                'examResults.exam' => function ($query) {
                    $query->select('id', 'name', 'exam_date');
                }
            ])
            ->orderBy('roll_number')
            ->get();

        // Format students with test counts
        $students = $classStudents->map(function ($classStudent) {
            $testCount = $classStudent->examResults->count();
            $exams = $classStudent->examResults->map(function ($result) {
                return [
                    'id' => $result->exam->id ?? null,
                    'name' => $result->exam->name ?? 'Unknown',
                    'date' => $result->exam->exam_date ?? null,
                ];
            })->sortByDesc('date')->values();

            return [
                'id' => $classStudent->student->id,
                'name' => $classStudent->student->name,
                'father_name' => $classStudent->student->father_name,
                'roll_number' => $classStudent->roll_number,
                'test_count' => $testCount,
                'exams' => $exams,
            ];
        });

        // Apply pagination manually
        $currentPage = (int) $request->get('page', 1);
        $total = $students->count();
        $lastPage = $perPage === 500 ? 1 : max(1, ceil($total / $perPage));
        
        // If "All" is selected (500), show all students
        $items = $perPage === 500 
            ? $students 
            : $students->forPage($currentPage, $perPage);

        return Inertia::render('Results/ClassStudents', [
            'classItem' => [
                'id' => $class->id,
                'name' => $class->name,
            ],
            'students' => $items->values(),
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
            'filters' => $request->only(['per_page']),
        ]);
    }

    /**
     * Export class report to Excel
     */
    public function exportClass(AcademicClass $class)
    {
        $fileName = str_replace(' ', '_', $class->name) . '_Report_' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new ClassReportExport($class), $fileName);
    }
}
