<?php

namespace App\Jobs;

use App\Services\ExcelImportService;
use App\Services\QuickUploadService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessQuickUpload implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $examName,
        public string $examDate,
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
        $quickUploadService = new QuickUploadService();

        try {
            // Read Excel file from storage
            $filePath = Storage::path($this->filePath);
            $rows = $excelService->extractDataRows($filePath, $this->columnMapping);

            // Process quick upload
            $results = $quickUploadService->processQuickUpload(
                $this->examName,
                $this->examDate,
                $rows,
                $this->columnMapping
            );

            // Store results in cache for retrieval (optional)
            $cacheKey = "quick_upload_{$results['exam_id']}_" . ($this->job->getJobId() ?? uniqid());
            cache()->put($cacheKey, $results, 3600);

            // Clean up uploaded file
            Storage::delete($this->filePath);
        } catch (\Exception $e) {
            // Log error
            \Log::error('Quick upload job failed: ' . $e->getMessage());
            // Clean up file even on error
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
            throw $e;
        }
    }
}
