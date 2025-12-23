<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\AcademicClass;
use App\Models\ClassStudent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Total students
        $totalStudents = Student::count();
        
        // Students who have given exams
        $studentsWithExams = ExamResult::distinct('class_student_id')
            ->count();
        
        // Total exams
        $totalExams = Exam::count();
        
        // Total exam results
        $totalExamResults = ExamResult::count();
        
        // Students by class (pie chart data)
        $studentsByClass = AcademicClass::withCount(['classStudents' => function ($query) {
            $query->where('is_active', true);
        }])
        ->having('class_students_count', '>', 0)
        ->get()
        ->map(function ($class) {
            return [
                'name' => $class->name,
                'count' => $class->class_students_count,
            ];
        });
        
        // Exam participation (students who gave exams vs total)
        $examParticipation = [
            'with_exams' => $studentsWithExams,
            'without_exams' => max(0, $totalStudents - $studentsWithExams),
        ];
        
        // Top performers by class (best average)
        $topPerformers = [];
        $classes = AcademicClass::has('classStudents')->get();
        
        foreach ($classes as $class) {
            $topPerformer = ExamResult::join('class_student', 'exam_results.class_student_id', '=', 'class_student.id')
                ->where('class_student.class_id', $class->id)
                ->where('class_student.is_active', true)
                ->select('class_student_id', DB::raw('AVG(exam_results.average) as avg_score'))
                ->groupBy('class_student_id')
                ->orderBy('avg_score', 'desc')
                ->with(['classStudent.student'])
                ->first();
            
            if ($topPerformer && $topPerformer->classStudent && $topPerformer->classStudent->student) {
                $topPerformers[] = [
                    'class_name' => $class->name,
                    'student_name' => $topPerformer->classStudent->student->name,
                    'average' => round($topPerformer->avg_score, 2),
                ];
            }
        }
        
        // Recent exams (last 5)
        $recentExams = Exam::withCount('examResults')
            ->orderBy('exam_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'date' => $exam->exam_date->format('M d, Y'),
                    'students_count' => $exam->exam_results_count,
                ];
            });
        
        // Exam results over time (for line chart)
        $examResultsOverTime = Exam::withCount('examResults')
            ->orderBy('exam_date', 'asc')
            ->get()
            ->map(function ($exam) {
                return [
                    'date' => $exam->exam_date->format('M d'),
                    'count' => $exam->exam_results_count,
                ];
            });
        
        // Subject-wise average marks
        $subjectAverages = DB::table('exam_subject_marks')
            ->join('subjects', 'exam_subject_marks.subject_id', '=', 'subjects.id')
            ->whereNotNull('exam_subject_marks.marks')
            ->select('subjects.name', DB::raw('AVG(exam_subject_marks.marks) as average'))
            ->groupBy('subjects.id', 'subjects.name')
            ->get()
            ->map(function ($item) {
                return [
                    'subject' => $item->name,
                    'average' => round($item->average, 2),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                'students_with_exams' => $studentsWithExams,
                'total_exams' => $totalExams,
                'total_exam_results' => $totalExamResults,
            ],
            'studentsByClass' => $studentsByClass,
            'examParticipation' => $examParticipation,
            'topPerformers' => $topPerformers,
            'recentExams' => $recentExams,
            'examResultsOverTime' => $examResultsOverTime,
            'subjectAverages' => $subjectAverages,
        ]);
    }
}
