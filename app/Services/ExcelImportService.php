<?php

namespace App\Services;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ExcelImportService
{
    /**
     * Read Excel file and return as array
     */
    public function readExcel($file): array
    {
        try {
            // Handle both file paths and uploaded file objects
            if (is_string($file)) {
                // File path - use directly
                $data = Excel::toArray([], $file);
            } else {
                // Uploaded file object - use the file directly
                // The Excel facade can handle UploadedFile objects
                $data = Excel::toArray([], $file);
            }
            
            if (empty($data) || empty($data[0])) {
                return [];
            }
            
            return $data[0]; // Return first sheet
        } catch (\Exception $e) {
            throw new \Exception('Error reading Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Get column headers from Excel
     */
    public function getHeaders($file): array
    {
        try {
            $data = $this->readExcel($file);
            
            if (empty($data)) {
                return [];
            }
            
            // Get first row as headers
            $headers = $data[0] ?? [];
            
            // Clean headers - convert to string and trim
            return array_map(function($header) {
                return trim((string) $header);
            }, $headers);
        } catch (\Exception $e) {
            throw new \Exception('Error reading headers: ' . $e->getMessage());
        }
    }

    /**
     * Map Excel columns to expected fields
     */
    public function mapColumns(array $headers, array $columnMapping): array
    {
        $mapped = [];
        foreach ($headers as $index => $header) {
            $header = trim(strtolower($header));
            if (isset($columnMapping[$header])) {
                $mapped[$columnMapping[$header]] = $index;
            }
        }
        return $mapped;
    }

    /**
     * Extract data rows from Excel (skip header row)
     */
    public function extractDataRows($file, array $columnMapping): array
    {
        $data = $this->readExcel($file);
        
        if (empty($data)) {
            return [];
        }
        
        $headers = array_shift($data); // Remove header row
        
        // Clean headers for mapping
        $cleanHeaders = array_map(function($header) {
            return trim(strtolower((string) $header));
        }, $headers);
        
        $mappedColumns = [];
        foreach ($columnMapping as $excelHeader => $field) {
            $excelHeaderLower = strtolower(trim($excelHeader));
            $index = array_search($excelHeaderLower, $cleanHeaders);
            if ($index !== false) {
                $mappedColumns[$field] = $index;
            }
        }
        
        $rows = [];
        foreach ($data as $rowIndex => $row) {
            $mappedRow = [];
            foreach ($mappedColumns as $field => $columnIndex) {
                $value = isset($row[$columnIndex]) ? $row[$columnIndex] : null;
                
                // Handle different data types from Excel
                if ($value !== null) {
                    // If it's a numeric value (like roll number stored as number), convert to string
                    if (is_numeric($value)) {
                        $value = (string) $value;
                    } else {
                        $value = (string) $value;
                    }
                    // Trim whitespace and remove any non-printable characters
                    $value = trim($value);
                    // Remove all internal whitespace for roll numbers
                    if ($field === 'roll_number') {
                        $value = preg_replace('/\s+/', '', $value);
                    }
                }
                
                $mappedRow[$field] = $value ?: null;
            }
            // Only add row if roll_number is present (for quick upload)
            if (!empty($mappedRow['roll_number'])) {
                $rows[] = $mappedRow;
            }
        }

        return $rows;
    }
}

