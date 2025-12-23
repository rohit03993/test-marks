<?php

namespace App\Jobs;

use App\Services\ExcelImportService;
use App\Services\StudentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessStudentUpload implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
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
        $studentService = new StudentService();

        try {
            // Read Excel file from storage
            $filePath = Storage::path($this->filePath);
            $rows = $excelService->extractDataRows($filePath, $this->columnMapping);

            // Process students
            $results = $studentService->processBulkUpload($rows);

            // Store results in cache for retrieval (using job ID if available)
            $cacheKey = "student_upload_results_" . ($this->job->getJobId() ?? uniqid());
            cache()->put($cacheKey, $results, 3600);

            // Clean up uploaded file
            Storage::delete($this->filePath);
        } catch (\Exception $e) {
            // Log error
            \Log::error('Student upload job failed: ' . $e->getMessage());
            // Clean up file even on error
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
            throw $e;
        }
    }
}
