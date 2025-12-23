<?php

namespace App\Http\Controllers;

use App\Services\ExcelImportService;
use App\Services\QuickUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class QuickUploadController extends Controller
{
    protected $excelService;

    public function __construct(ExcelImportService $excelService)
    {
        $this->excelService = $excelService;
    }

    /**
     * Show quick upload page
     */
    public function index()
    {
        return Inertia::render('QuickUpload/Index');
    }

    /**
     * Handle quick exam result upload - Process immediately (synchronously)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'exam_name' => 'required|string|max:255',
            'exam_date' => 'required|date',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'column_mapping' => 'required|array',
            'column_mapping.roll_number' => 'required|string',
            'column_mapping.physics' => 'nullable|string',
            'column_mapping.chemistry' => 'nullable|string',
            'column_mapping.mathematics' => 'nullable|string',
        ]);

        $tempPath = null;
        try {
            // Store uploaded file
            $tempPath = $request->file('file')->store('temp', 'local');
            $filePath = Storage::path($tempPath);
            
            // Normalize column mapping keys to lowercase
            $columnMapping = [];
            foreach ($request->column_mapping as $key => $value) {
                if ($value) {
                    $columnMapping[strtolower(trim($value))] = $key;
                }
            }

            // Process immediately (synchronously) for quick upload
            $excelService = new ExcelImportService();
            $quickUploadService = new QuickUploadService();
            
            // Read Excel file
            $rows = $excelService->extractDataRows($filePath, $columnMapping);
            
            // Process quick upload
            $results = $quickUploadService->processQuickUpload(
                $request->exam_name,
                $request->exam_date,
                $rows,
                $columnMapping
            );

            // Clean up uploaded file
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }

            // Build success message
            $message = "Exam '{$request->exam_name}' created successfully! ";
            $message .= "Processed {$results['processed']} students. ";
            
            if (count($results['unmapped']) > 0) {
                $unmappedRollNumbers = array_slice(
                    array_map(function($item) {
                        return $item['roll_number'] ?? $item['roll_number_original'] ?? 'N/A';
                    }, $results['unmapped']),
                    0,
                    10 // Show first 10 unmapped
                );
                
                $message .= count($results['unmapped']) . " students could not be matched. ";
                if (count($results['unmapped']) <= 10) {
                    $message .= "Unmapped roll numbers: " . implode(', ', $unmappedRollNumbers);
                } else {
                    $message .= "First 10 unmapped: " . implode(', ', $unmappedRollNumbers) . "...";
                }
            }
            
            if (count($results['errors']) > 0) {
                $message .= " " . count($results['errors']) . " errors occurred during processing.";
            }

            return redirect()->route('exams.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            // Clean up file on error
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            
            \Log::error('Quick upload failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get Excel headers for column mapping
     */
    public function getHeaders(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $tempPath = null;
        try {
            $file = $request->file('file');
            
            if (!$file->isValid()) {
                return response()->json(['error' => 'Invalid file uploaded'], 422);
            }
            
            // Store file temporarily to get a valid path
            $tempPath = $file->store('temp', 'local');
            $fullPath = Storage::path($tempPath);
            
            $headers = $this->excelService->getHeaders($fullPath);
            
            // Clean up temp file
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            
            if (empty($headers)) {
                return response()->json(['error' => 'Could not read headers from file. Please ensure the file has a header row.'], 422);
            }
            
            return response()->json(['headers' => $headers]);
        } catch (\Exception $e) {
            // Clean up temp file on error
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            
            \Log::error('Error getting headers: ' . $e->getMessage());
            return response()->json(['error' => 'Error reading file headers: ' . $e->getMessage()], 422);
        }
    }
}
