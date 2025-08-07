<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SLSController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240' // 10MB max, Excel only
        ]);

        try {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileExtension = strtolower($file->getClientOriginalExtension());
            
            Log::info('SLS File upload started', [
                'fileName' => $fileName,
                'fileSize' => $file->getSize(),
                'fileExtension' => $fileExtension
            ]);
            
            // Store the file temporarily
            $filePath = $file->storeAs('temp', $fileName, 'local');
            $fullPath = Storage::disk('local')->path($filePath);
            
            Log::info('SLS File stored at', ['path' => $fullPath]);
            
            $extractedData = $this->processExcelFile($fullPath);
            Log::info('SLS Excel processed', ['extractedRows' => count($extractedData['structured_data'] ?? [])]);
            
            // Clean up temporary file
            Storage::disk('local')->delete($filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'File processed successfully',
                'data' => $extractedData
            ]);
            
        } catch (\Exception $e) {
            Log::error('SLS File upload error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processExcelFile($filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            Log::info('Excel file loaded', ['totalRows' => count($rows)]);

            $structured = [];
            $startRow = null;
            $lastKnownStateName = ''; // Track the last known state name for merged cells

            // Find the start of data (skip headers)
            for ($i = 0; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (is_array($row) && count($row) > 0) {
                    $firstCell = trim($row[0] ?? '');
                    // Look for the header row that contains "State Linked Scheme (SLS)"
                    if (stripos($firstCell, 'State Linked Scheme') !== false || 
                        stripos($firstCell, 'SLS') !== false) {
                        $startRow = $i + 1; // Data starts from next row
                        break;
                    }
                }
            }

            if ($startRow === null) {
                // If header not found, start from row 2 (assuming first row is header)
                $startRow = 1;
            }

            Log::info('Data processing started from row', ['startRow' => $startRow]);

            // Process data rows
            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                if (!is_array($row) || count($row) < 6) {
                    continue; // Skip empty or invalid rows
                }

                // Extract data based on the expected structure
                $currentStateName = trim($row[3] ?? ''); // State Name column
                
                // Handle merged state name cells
                if (!empty($currentStateName)) {
                    // If we have a new state name, update our tracking
                    $lastKnownStateName = $currentStateName;
                } else {
                    // If state name is empty, use the last known state name
                    $currentStateName = $lastKnownStateName;
                }
                
                $slsFull = trim($row[4] ?? ''); // State Linked Scheme (SLS) column
                $sgAccount = trim($row[5] ?? ''); // SG Account column
                $sharingPatternCentre = trim($row[6] ?? ''); // Sharing Pattern Centre column
                $sharingPatternState = trim($row[7] ?? ''); // Sharing Pattern State column

                // Skip empty rows
                if (empty($currentStateName) && empty($slsFull)) {
                    continue;
                }

                // Parse SLS Code and Name from the SLS column
                $slsCode = '';
                $slsName = '';
                
                if (!empty($slsFull)) {
                    // Expected format: "AP17 - National Food Security"
                    if (preg_match('/^([A-Z]{2}\d+)\s*-\s*(.+)$/', $slsFull, $matches)) {
                        $slsCode = trim($matches[1]);
                        $slsName = trim($matches[2]);
                    } else {
                        // If format doesn't match, use the whole string as name
                        $slsName = $slsFull;
                    }
                }

                // Only add if we have meaningful data
                if (!empty($currentStateName) || !empty($slsName)) {
                    $structured[] = [
                        'sls_code' => $slsCode,
                        'sls_name' => $slsName,
                        'state_name' => $currentStateName,
                        'sg_account' => $sgAccount,
                        'sharing_pattern_centre' => $sharingPatternCentre,
                        'sharing_pattern_state' => $sharingPatternState,
                    ];
                }
            }

            Log::info('SLS data extracted', ['structuredCount' => count($structured)]);

            return [
                'type' => 'excel',
                'structured_data' => $structured,
                'total_items' => count($structured)
            ];

        } catch (\Exception $e) {
            Log::error('Error processing Excel file', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
} 