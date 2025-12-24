<?php

namespace App\Exports;

use App\Models\AcademicClass;
use App\Models\ClassStudent;
use App\Models\Exam;
use App\Models\ExamResult;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ClassReportExport implements WithMultipleSheets
{
    protected $class;
    protected $classStudents;
    protected $exams;
    protected $studentStats;

    public function __construct(AcademicClass $class)
    {
        $this->class = $class;
        
        // Get all active students in this class
        $this->classStudents = ClassStudent::where('class_id', $class->id)
            ->where('is_active', true)
            ->with(['student', 'examResults.exam.subjects', 'examResults.subjectMarks.subject'])
            ->orderBy('roll_number')
            ->get();
        
        // Get all exams for this class
        $this->exams = $class->exams()
            ->with('subjects')
            ->orderBy('exam_date', 'desc')
            ->get();
        
        // Calculate student statistics
        $this->calculateStudentStats();
    }

    /**
     * Calculate statistics for each student
     */
    protected function calculateStudentStats()
    {
        $this->studentStats = [];
        
        foreach ($this->classStudents as $classStudent) {
            $grandTotal = 0;
            $markCount = 0;
            $examsAttended = 0;
            $examsNotAttended = 0;
            
            foreach ($this->exams as $exam) {
                $examResult = $classStudent->examResults->firstWhere('exam_id', $exam->id);
                
                if ($examResult && $examResult->status === 'present') {
                    $examsAttended++;
                    if ($examResult->average !== null) {
                        $grandTotal += $examResult->average;
                        $markCount++;
                    }
                } else {
                    $examsNotAttended++;
                }
            }
            
            $grandAverage = $markCount > 0 ? $grandTotal / $markCount : 0;
            
            $this->studentStats[$classStudent->id] = [
                'grand_total' => $grandTotal,
                'grand_average' => $grandAverage,
                'exams_attended' => $examsAttended,
                'exams_not_attended' => $examsNotAttended,
                'total_exams' => $this->exams->count(),
            ];
        }
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // Add Summary sheet first
        $sheets[] = new ClassReportSummarySheet($this->class, $this->classStudents, $this->exams, $this->studentStats);
        
        // Add one sheet per exam
        foreach ($this->exams as $exam) {
            $sheets[] = new ClassReportExamSheet($this->class, $this->classStudents, $exam);
        }
        
        // Add Consolidated sheet
        $sheets[] = new ClassReportConsolidatedSheet($this->class, $this->classStudents, $this->exams, $this->studentStats);
        
        return $sheets;
    }
}

// Summary Sheet
class ClassReportSummarySheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $class;
    protected $classStudents;
    protected $exams;
    protected $studentStats;

    public function __construct($class, $classStudents, $exams, $studentStats)
    {
        $this->class = $class;
        $this->classStudents = $classStudents;
        $this->exams = $exams;
        $this->studentStats = $studentStats;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        return [];
    }

    public function array(): array
    {
        $data = [];
        
        // Header
        $data[] = ['CLASS REPORT SUMMARY'];
        $data[] = [];
        $data[] = ['Class Name:', $this->class->name];
        $data[] = ['Total Students:', $this->classStudents->count()];
        $data[] = ['Total Exams:', $this->exams->count()];
        $data[] = [];
        
        // Exam Statistics
        $data[] = ['EXAM STATISTICS'];
        $data[] = ['Exam Name', 'Date', 'Students Attended', 'Class Average'];
        
        foreach ($this->exams as $exam) {
            $examResults = ExamResult::where('exam_id', $exam->id)
                ->whereIn('class_student_id', $this->classStudents->pluck('id'))
                ->where('status', 'present')
                ->get();
            
            $studentsAttended = $examResults->count();
            $classAverage = $examResults->count() > 0 
                ? $examResults->avg('average') 
                : 0;
            
            $data[] = [
                $exam->name,
                $exam->exam_date ? date('Y-m-d', strtotime($exam->exam_date)) : 'N/A',
                $studentsAttended,
                number_format($classAverage, 2)
            ];
        }
        
        $data[] = [];
        $data[] = [];
        
        // Top 3 Students
        $data[] = ['TOP 3 STUDENTS (By Grand Average)'];
        $data[] = ['Rank', 'Roll Number', 'Name', 'Grand Average', 'Exams Attended'];
        
        $topStudents = $this->classStudents->map(function ($cs) {
            return [
                'id' => $cs->id,
                'roll_number' => $cs->roll_number,
                'name' => $cs->student->name,
                'grand_average' => $this->studentStats[$cs->id]['grand_average'] ?? 0,
                'exams_attended' => $this->studentStats[$cs->id]['exams_attended'] ?? 0,
            ];
        })
        ->filter(function ($student) {
            return $student['grand_average'] > 0;
        })
        ->sortByDesc('grand_average')
        ->take(3)
        ->values();
        
        $rank = 1;
        foreach ($topStudents as $student) {
            $data[] = [
                $rank++,
                $student['roll_number'],
                $student['name'],
                number_format($student['grand_average'], 2),
                $student['exams_attended']
            ];
        }
        
        $data[] = [];
        $data[] = [];
        
        // Bottom 3 Students
        $data[] = ['BOTTOM 3 STUDENTS (By Grand Average)'];
        $data[] = ['Rank', 'Roll Number', 'Name', 'Grand Average', 'Exams Attended'];
        
        $bottomStudents = $this->classStudents->map(function ($cs) {
            return [
                'id' => $cs->id,
                'roll_number' => $cs->roll_number,
                'name' => $cs->student->name,
                'grand_average' => $this->studentStats[$cs->id]['grand_average'] ?? 0,
                'exams_attended' => $this->studentStats[$cs->id]['exams_attended'] ?? 0,
            ];
        })
        ->filter(function ($student) {
            return ($student['grand_average'] ?? 0) > 0;
        })
        ->sortBy('grand_average')
        ->take(3)
        ->values();
        
        $rank = 1;
        foreach ($bottomStudents as $student) {
            $data[] = [
                $rank++,
                $student['roll_number'],
                $student['name'],
                number_format($student['grand_average'], 2),
                $student['exams_attended']
            ];
        }
        
        if ($bottomStudents->isEmpty()) {
            $data[] = ['No students with exam results'];
        }
        
        $data[] = [];
        $data[] = [];
        
        // Students Not Attending Exams
        $data[] = ['STUDENTS WITH MISSED EXAMS'];
        $data[] = ['Roll Number', 'Name', 'Exams Not Attended', 'Total Exams'];
        
        $studentsWithMissedExams = $this->classStudents->map(function ($cs) {
            return [
                'roll_number' => $cs->roll_number,
                'name' => $cs->student->name,
                'exams_not_attended' => $this->studentStats[$cs->id]['exams_not_attended'] ?? 0,
                'total_exams' => $this->studentStats[$cs->id]['total_exams'] ?? 0,
            ];
        })
        ->filter(function ($student) {
            return $student['exams_not_attended'] > 0;
        })
        ->sortByDesc('exams_not_attended')
        ->values();
        
        foreach ($studentsWithMissedExams as $student) {
            $data[] = [
                $student['roll_number'],
                $student['name'],
                $student['exams_not_attended'],
                $student['total_exams']
            ];
        }
        
        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
            7 => ['font' => ['bold' => true, 'size' => 12], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
            8 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D0D0D0']]],
        ];
    }
}

// Individual Exam Sheet
class ClassReportExamSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $class;
    protected $classStudents;
    protected $exam;

    public function __construct($class, $classStudents, $exam)
    {
        $this->class = $class;
        $this->classStudents = $classStudents;
        $this->exam = $exam;
    }

    public function title(): string
    {
        // Excel sheet names are limited to 31 characters
        $title = $this->exam->name . ' ' . ($this->exam->exam_date ? date('M d', strtotime($this->exam->exam_date)) : '');
        return substr($title, 0, 31);
    }

    public function headings(): array
    {
        $headings = ['Roll Number', 'Student Name', 'Father Name'];
        
        // Add subject columns
        foreach ($this->exam->subjects as $subject) {
            $headings[] = $subject->name;
        }
        
        $headings[] = 'Total';
        $headings[] = 'Average';
        $headings[] = 'Status';
        
        return $headings;
    }

    public function array(): array
    {
        $data = [];
        
        foreach ($this->classStudents as $classStudent) {
            $examResult = $classStudent->examResults->firstWhere('exam_id', $this->exam->id);
            
            $row = [
                $classStudent->roll_number,
                $classStudent->student->name,
                $classStudent->student->father_name ?? '-',
            ];
            
            // Add subject marks
            if ($examResult) {
                $subjectMarksMap = [];
                foreach ($examResult->subjectMarks as $subjectMark) {
                    if ($subjectMark->subject) {
                        $subjectMarksMap[$subjectMark->subject->id] = $subjectMark->marks;
                    }
                }
                
                foreach ($this->exam->subjects as $subject) {
                    $mark = $subjectMarksMap[$subject->id] ?? null;
                    $row[] = $mark !== null ? number_format($mark, 2) : 'Absent';
                }
                
                $row[] = $examResult->total ? number_format($examResult->total, 2) : '0.00';
                $row[] = $examResult->average ? number_format($examResult->average, 2) : '0.00';
                $row[] = $examResult->status === 'present' ? 'Present' : 'Absent';
            } else {
                // Student didn't take this exam
                foreach ($this->exam->subjects as $subject) {
                    $row[] = 'Not Taken';
                }
                $row[] = '0.00';
                $row[] = '0.00';
                $row[] = 'Absent';
            }
            
            $data[] = $row;
        }
        
        return $data;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 15, // Roll Number
            'B' => 25, // Student Name
            'C' => 25, // Father Name
        ];
        
        $col = 'D';
        foreach ($this->exam->subjects as $subject) {
            $widths[$col] = 15;
            $col++;
        }
        
        $widths[$col++] = 12; // Total
        $widths[$col++] = 12; // Average
        $widths[$col] = 12;   // Status
        
        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            ],
        ];
    }
}

// Consolidated Sheet
class ClassReportConsolidatedSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $class;
    protected $classStudents;
    protected $exams;
    protected $studentStats;

    public function __construct($class, $classStudents, $exams, $studentStats)
    {
        $this->class = $class;
        $this->classStudents = $classStudents;
        $this->exams = $exams;
        $this->studentStats = $studentStats;
    }

    public function title(): string
    {
        return 'Consolidated';
    }

    public function headings(): array
    {
        $headings = ['Roll Number', 'Student Name', 'Father Name'];
        
        // Add columns for each exam (Total and Average)
        foreach ($this->exams as $exam) {
            $headings[] = $exam->name . ' Total';
            $headings[] = $exam->name . ' Avg';
        }
        
        $headings[] = 'Grand Total';
        $headings[] = 'Grand Average';
        $headings[] = 'Exams Attended';
        $headings[] = 'Exams Not Attended';
        
        return $headings;
    }

    public function array(): array
    {
        $data = [];
        
        foreach ($this->classStudents as $classStudent) {
            $row = [
                $classStudent->roll_number,
                $classStudent->student->name,
                $classStudent->student->father_name ?? '-',
            ];
            
            // Add exam totals and averages
            foreach ($this->exams as $exam) {
                $examResult = $classStudent->examResults->firstWhere('exam_id', $exam->id);
                
                if ($examResult && $examResult->status === 'present') {
                    $row[] = $examResult->total ? number_format($examResult->total, 2) : '0.00';
                    $row[] = $examResult->average ? number_format($examResult->average, 2) : '0.00';
                } else {
                    $row[] = '-';
                    $row[] = '-';
                }
            }
            
            // Add grand totals
            $stats = $this->studentStats[$classStudent->id] ?? [];
            $row[] = number_format($stats['grand_total'] ?? 0, 2);
            $row[] = number_format($stats['grand_average'] ?? 0, 2);
            $row[] = $stats['exams_attended'] ?? 0;
            $row[] = $stats['exams_not_attended'] ?? 0;
            
            $data[] = $row;
        }
        
        return $data;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 15, // Roll Number
            'B' => 25, // Student Name
            'C' => 25, // Father Name
        ];
        
        $col = 'D';
        foreach ($this->exams as $exam) {
            $widths[$col++] = 15; // Total
            $widths[$col++] = 15; // Average
        }
        
        $widths[$col++] = 15; // Grand Total
        $widths[$col++] = 15; // Grand Average
        $widths[$col++] = 15; // Exams Attended
        $widths[$col] = 18;   // Exams Not Attended
        
        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            ],
        ];
    }
}
