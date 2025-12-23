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
        Schema::create('exam_subject_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_result_id')->constrained('exam_results')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->decimal('marks', 5, 2)->nullable(); // NULL = absent
            $table->timestamps();

            // One mark per subject per exam result
            $table->unique(['exam_result_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_subject_marks');
    }
};
