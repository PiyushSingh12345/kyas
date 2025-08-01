<?php

namespace App\Http\Controllers;

use App\Models\BudgetHead;
use App\Models\BudgetPhase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BudgetHeadController extends Controller
{
    public function index()
    {
        return Inertia::render('Budget_allocation/BudgetHeads', [
            'BudgetHeads' => BudgetHead::latest()->get() 
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'budget' => 'required|max:255',
            'description' => 'required|string|max:255',
            'category' => 'required'
        ]);

        BudgetHead::create([
        ...$validated,
        'status' => '1',
        ]);

        

        return redirect()->back()->with('success', 'Budget Heads added successfully!');

    }

    public function destroy(BudgetHead $budgetHead)
    {
        $budgetHead->update(['status' => '0']);

        return redirect()->route('BudgetHead.index')
                         ->with('success', 'Budget Head deactivated successfully.');
    }

    public function update(Request $request, BudgetHead $budgetHead)
    {
        $validated = $request->validate([
            'budget' => 'required|max:255',
            'description' => 'required|string|max:255',
            'category' => 'required'
        ]);

        $budgetHead->update($validated);

        return redirect()->back()->with('success', 'Budget Head updated successfully!');
    }
    public function toggleStatus($id, Request $request)
    {
        $budgetHead = BudgetHead::findOrFail($id);
        $budgetHead->status = $request->status;
        $budgetHead->save();

        return back()->with('success', 'Status updated successfully!');
    }

     public function fetchBudgetHeads(Request $request)
    {
        
        $budgetHeads = BudgetHead::where('status', 1)->get();

        return response()->json($budgetHeads);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240' // 10MB max, PDF only for now
        ]);

        try {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileExtension = strtolower($file->getClientOriginalExtension());
            
            \Log::info('File upload started', [
                'fileName' => $fileName,
                'fileSize' => $file->getSize(),
                'fileExtension' => $fileExtension
            ]);
            
            // Store the file temporarily
            $filePath = $file->storeAs('temp', $fileName, 'local');
            $fullPath = Storage::disk('local')->path($filePath);
            
            \Log::info('File stored at', ['path' => $fullPath]);
            
            $extractedData = [];
            
            if ($fileExtension === 'pdf') {
                $extractedData = $this->processPdfFile($fullPath);
                \Log::info('PDF processed', ['extractedLines' => count($extractedData['extracted_lines'] ?? [])]);
            }
            
            // Clean up temporary file
            Storage::disk('local')->delete($filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'File processed successfully',
                'data' => $extractedData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('File upload error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processPdfFile($filePath)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        // Extract Krishonnati Yojna section
        $matches = [];
        // if (preg_match('/Krishonnati Yojna(.*?)Rashtriya Krishi Vikas Yojna/s', $text, $matches)) {
        if (preg_match('/Krishonnati Yojna(.*?)National Mission on Natural Farming/s', $text, $matches)) {
            $section = trim($matches[1]);

            // Split lines and clean
            $lines = array_filter(array_map('trim', explode("\n", $section)));
            $lines = array_values($lines); // Re-index array to ensure sequential indices

            $structured = [];
            for ($i = 0; $i < count($lines); $i++) {
                // Skip unwanted rows
                if (isset($lines[$i])) {
                    $currentLine = trim($lines[$i]);
                    
                    // Skip lines containing unwanted text
                    if (stripos($currentLine, 'Details of BE 2025-26 under Demand-01 (DA&FW)') !== false ||
                        stripos($currentLine, 'Rs Lakh') !== false) {
                        continue; // Skip this line and move to next
                    }
                }
                
                // Check if current line exists and matches pattern for budget codes
                if (isset($lines[$i]) && preg_match('/^(\d{12,15})-(.+)$/', $lines[$i], $parts)) {
                    $code = trim($parts[1]);
                    $item = trim($parts[2]);
                    $be_2024_25 = null;
                    $be_2025_26 = null;
                    
                    // Extract amounts from the current line if they exist
                    if (preg_match_all('/\d+\.\d{2}/', $lines[$i], $amounts)) {
                        if (isset($amounts[0][0])) {
                            $be_2024_25 = $amounts[0][0];
                        }
                        if (isset($amounts[0][1])) {
                            $be_2025_26 = $amounts[0][1];
                        }
                    }
                    
                    // If amounts not found in current line, check next lines
                    if (!$be_2024_25 && isset($lines[$i + 1])) {
                        $nextLine = trim($lines[$i + 1]);
                        if (preg_match('/^\d+\.\d{2}$/', $nextLine)) {
                            $be_2024_25 = $nextLine;
                        }
                    }
                    
                    if (!$be_2025_26 && isset($lines[$i + 2])) {
                        $nextLine = trim($lines[$i + 2]);
                        if (preg_match('/^\d+\.\d{2}$/', $nextLine)) {
                            $be_2025_26 = $nextLine;
                        }
                    }
                    
                    // Clean the item description by removing amounts and tabs
                    $cleanItem = preg_replace('/\s*\d+\.\d{2}\s*\d+\.\d{2}.*$/', '', $item);
                    $cleanItem = preg_replace('/\s*\d+\.\d{2}.*$/', '', $cleanItem);
                    $cleanItem = str_replace("\t", " ", $cleanItem);
                    $cleanItem = trim($cleanItem);
                    
                    $structured[] = [
                        'code' => $code,
                        'item' => $cleanItem,
                        'be_2024_25' => $be_2024_25,
                        'be_2025_26' => $be_2025_26,
                    ];
                    
                    // Skip amount lines if they were on separate lines
                    if ($be_2024_25 && $be_2024_25 === trim($lines[$i + 1] ?? '')) {
                        $i++;
                    }
                    if ($be_2025_26 && $be_2025_26 === trim($lines[$i + 1] ?? '')) {
                        $i++;
                    }
                }
            }

            return [
                'type' => 'pdf',
                'structured_data' => $structured,
                'total_items' => count($structured)
            ];
        }

        // If Krishonnati Yojna section not found, try to extract any budget-like data
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $lines = array_values($lines);
        
        $structured = [];
        for ($i = 0; $i < count($lines); $i++) {
            // Skip unwanted rows
            if (isset($lines[$i])) {
                $currentLine = trim($lines[$i]);
                
                // Skip lines containing unwanted text
                if (stripos($currentLine, 'Details of BE 2025-26 under Demand-01 (DA&FW)') !== false ||
                    stripos($currentLine, 'Rs Lakh') !== false) {
                    continue; // Skip this line and move to next
                }
            }
            
            if (isset($lines[$i]) && preg_match('/^(\d{12,15})-(.+)$/', $lines[$i], $parts)) {
                $code = trim($parts[1]);
                $item = trim($parts[2]);
                $be_2024_25 = null;
                $be_2025_26 = null;
                
                // Extract amounts from the current line if they exist
                if (preg_match_all('/\d+\.\d{2}/', $lines[$i], $amounts)) {
                    if (isset($amounts[0][0])) {
                        $be_2024_25 = $amounts[0][0];
                    }
                    if (isset($amounts[0][1])) {
                        $be_2025_26 = $amounts[0][1];
                    }
                }
                
                // Clean the item description by removing amounts and tabs
                $cleanItem = preg_replace('/\s*\d+\.\d{2}\s*\d+\.\d{2}.*$/', '', $item);
                $cleanItem = preg_replace('/\s*\d+\.\d{2}.*$/', '', $cleanItem);
                $cleanItem = str_replace("\t", " ", $cleanItem);
                $cleanItem = trim($cleanItem);
                
                $structured[] = [
                    'code' => $code,
                    'item' => $cleanItem,
                    'be_2024_25' => $be_2024_25,
                    'be_2025_26' => $be_2025_26,
                ];
            }
        }
        
        if (count($structured) > 0) {
            return [
                'type' => 'pdf',
                'structured_data' => $structured,
                'total_items' => count($structured)
            ];
        }

        return [
            'type' => 'pdf',
            'error' => 'Krishonnati Yojna section not found and no budget data could be extracted',
            'structured_data' => [],
            'total_items' => 0
        ];
    }

    public function import(Request $request)
    {
        $request->validate([
            'structured_data' => 'required|array',
            'structured_data.*.code' => 'required|string',
            'structured_data.*.item' => 'required|string',
            'structured_data.*.be_2024_25' => 'nullable|string',
            'structured_data.*.be_2025_26' => 'nullable|string',
            'file_name' => 'required|string'
        ]);

        try {
            $structuredData = $request->structured_data;
            $fileName = $request->file_name;
            
            // Import structured data to database
            $importedCount = 0;
            $budgetPhaseCount = 0;
            
            foreach ($structuredData as $item) {
                // Check if budget head already exists
                $existing = BudgetHead::where('budget', $item['code'])->first();
                
                if (!$existing) {
                    // Create budget head
                    $budgetHead = BudgetHead::create([
                        'budget' => $item['code'],
                        'description' => $item['item'],
                        'category' => 'Gen', // Default category
                        'status' => 1
                    ]);
                    $importedCount++;
                    
                    // Create budget phase record for BE 2025-26
                    if (!empty($item['be_2025_26'])) {
                        BudgetPhase::create([
                            'financial_year' => '2025-26',
                            'budget_phase' => 'BE',
                            'budget_head_id' => $budgetHead->id,
                            'budget_amount' => (float) $item['be_2025_26'],
                            'status' => 1,
                            'draft_flag' => 0
                        ]);
                        $budgetPhaseCount++;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$importedCount} new budget heads and {$budgetPhaseCount} budget phases from {$fileName}",
                'imported_count' => $importedCount,
                'budget_phase_count' => $budgetPhaseCount,
                'total_processed' => count($structuredData)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing data: ' . $e->getMessage()
            ], 500);
        }
    }


}
