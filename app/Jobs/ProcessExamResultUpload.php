<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Services\ExcelImportService;
use App\Services\ExamResultService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessExamResultUpload implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $examId,
        public int $classId,
        public string $filePath,
        public array $columnMapping
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $excelService = new ExcelImportService();
        $examResultService = new ExamResultService();

        try {
            $exam = Exam::findOrFail($this->examId);
            
            // Read Excel file from storage
            $filePath = Storage::path($this->filePath);
            $rows = $excelService->extractDataRows($filePath, $this->columnMapping);

            // Process exam results for the specific class
            $results = $examResultService->processExamResults($exam, $this->classId, $rows, $this->columnMapping);

            // Store results in cache for retrieval
            $cacheKey = "exam_result_upload_{$this->examId}_" . ($this->job->getJobId() ?? uniqid());
            cache()->put($cacheKey, $results, 3600);

            // Clean up uploaded file
            Storage::delete($this->filePath);
        } catch (\Exception $e) {
            // Log error
            \Log::error('Exam result upload job failed: ' . $e->getMessage());
            // Clean up file even on error
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
            throw $e;
        }
    }
}
