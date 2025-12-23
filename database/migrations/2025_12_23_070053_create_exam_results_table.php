<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('class_student_id')->constrained('class_student')->onDelete('cascade');
            $table->decimal('total', 5, 2)->default(0);
            $table->decimal('average', 5, 2)->default(0);
            $table->enum('status', ['present', 'absent'])->default('present');
            $table->timestamps();

            // One result per student per exam
            $table->unique(['exam_id', 'class_student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
