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
        // Migrate existing class_id data to pivot table
        DB::statement('INSERT INTO class_exam (exam_id, class_id, created_at, updated_at)
            SELECT id, class_id, created_at, updated_at FROM exams WHERE class_id IS NOT NULL
            ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)');

        // Remove class_id column from exams table
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add class_id column back
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('exam_date')->constrained('classes')->onDelete('cascade');
        });

        // Migrate data back (take first class from pivot)
        DB::statement('UPDATE exams e
            INNER JOIN (
                SELECT exam_id, MIN(class_id) as class_id
                FROM class_exam
                GROUP BY exam_id
            ) ce ON e.id = ce.exam_id
            SET e.class_id = ce.class_id');
    }
};
