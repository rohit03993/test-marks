<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, handle existing duplicates by keeping the latest record and merging data
        $duplicates = DB::table('students')
            ->select('roll_number', DB::raw('COUNT(*) as count'))
            ->whereNotNull('roll_number')
            ->where('roll_number', '!=', '')
            ->groupBy('roll_number')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // Get all students with this roll number, ordered by created_at (newest first)
            $students = DB::table('students')
                ->where('roll_number', $duplicate->roll_number)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($students->count() > 1) {
                // Keep the first (newest) record
                $keepStudent = $students->first();
                
                // Merge data from all duplicates into the kept record
                $mergedName = $keepStudent->name;
                $mergedFatherName = $keepStudent->father_name;
                
                // Get latest non-null values from all duplicates
                foreach ($students as $student) {
                    if (!empty($student->name)) {
                        $mergedName = $student->name;
                    }
                    if (!empty($student->father_name)) {
                        $mergedFatherName = $student->father_name;
                    }
                }
                
                // Update the kept record with merged data
                DB::table('students')
                    ->where('id', $keepStudent->id)
                    ->update([
                        'name' => $mergedName,
                        'father_name' => $mergedFatherName ?: null,
                    ]);
                
                // Delete all other duplicates
                $idsToDelete = $students->skip(1)->pluck('id')->toArray();
                if (!empty($idsToDelete)) {
                    // Before deleting, we need to handle related records
                    // Update class_student records to point to the kept student
                    DB::table('class_student')
                        ->whereIn('student_id', $idsToDelete)
                        ->update(['student_id' => $keepStudent->id]);
                    
                    // Now delete duplicate students
                    DB::table('students')
                        ->whereIn('id', $idsToDelete)
                        ->delete();
                }
            }
        }

        // Now add unique constraint on roll_number
        Schema::table('students', function (Blueprint $table) {
            // First, make sure we can add unique constraint (handle nulls)
            // MySQL allows multiple NULLs in unique constraint, which is fine
            $table->unique('roll_number', 'students_roll_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_roll_number_unique');
        });
    }
};
