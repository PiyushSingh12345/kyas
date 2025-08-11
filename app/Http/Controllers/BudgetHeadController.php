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
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $budgetHeads = BudgetHead::latest()->paginate($perPage);
        
        return Inertia::render('Budget_allocation/BudgetHeads', [
            'BudgetHeads' => $budgetHeads->items(),
            'pagination' => [
                'current_page' => $budgetHeads->currentPage(),
                'last_page' => $budgetHeads->lastPage(),
                'per_page' => $budgetHeads->perPage(),
                'total' => $budgetHeads->total(),
                'from' => $budgetHeads->firstItem(),
                'to' => $budgetHeads->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'budget' => 'required|max:255',
            'description' => 'required|string|max:255',
            'category' => 'required|in:Gen,SC,ST,Capital-Gen,Capital-SC,Capital-ST,DAJUGA,Others'
        ]);

        // Format the budget head code before saving
        $formattedBudget = $this->formatBudgetHeadCode($validated['budget']);

        BudgetHead::create([
            'budget' => $formattedBudget,
            'description' => $validated['description'],
            'category' => $validated['category'],
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
            'category' => 'required|in:Gen,SC,ST,Capital-Gen,Capital-SC,Capital-ST,DAJUGA,Others'
        ]);

        // Format the budget head code before updating
        $formattedBudget = $this->formatBudgetHeadCode($validated['budget']);

        $budgetHead->update([
            'budget' => $formattedBudget,
            'description' => $validated['description'],
            'category' => $validated['category']
        ]);

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
            
            Log::info('File upload started', [
                'fileName' => $fileName,
                'fileSize' => $file->getSize(),
                'fileExtension' => $fileExtension
            ]);
            
            // Store the file temporarily
            $filePath = $file->storeAs('temp', $fileName, 'local');
            $fullPath = Storage::disk('local')->path($filePath);
            
            Log::info('File stored at', ['path' => $fullPath]);
            
            $extractedData = [];
            
            if ($fileExtension === 'pdf') {
                $extractedData = $this->processPdfFile($fullPath);
                Log::info('PDF processed', ['extractedLines' => count($extractedData['extracted_lines'] ?? [])]);
            }
            
            // Clean up temporary file
            Storage::disk('local')->delete($filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'File processed successfully',
                'data' => $extractedData
            ]);
            
        } catch (\Exception $e) {
            Log::error('File upload error', [
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
                    
                    // Handle multi-line descriptions
                    $fullDescription = $item;
                    $nextLineIndex = $i + 1;
                    
                    // Continue reading next lines until we find amounts or a new budget code
                    while ($nextLineIndex < count($lines) && 
                           isset($lines[$nextLineIndex]) && 
                           !preg_match('/^(\d{12,15})-(.+)$/', $lines[$nextLineIndex]) && // Not a new budget code
                           !preg_match('/^\d+\.\d{2}$/', $lines[$nextLineIndex])) { // Not an amount line
                        
                        $nextLine = trim($lines[$nextLineIndex]);
                        
                        // Skip unwanted lines
                        if (stripos($nextLine, 'Details of BE 2025-26 under Demand-01 (DA&FW)') !== false ||
                            stripos($nextLine, 'Rs Lakh') !== false) {
                            $nextLineIndex++;
                            continue;
                        }
                        
                        // If this line contains amounts, extract them and stop
                        if (preg_match_all('/\d+\.\d{2}/', $nextLine, $amounts)) {
                            if (!$be_2024_25 && isset($amounts[0][0])) {
                                $be_2024_25 = $amounts[0][0];
                            }
                            if (!$be_2025_26 && isset($amounts[0][1])) {
                                $be_2025_26 = $amounts[0][1];
                            }
                            break; // Stop reading lines as we found amounts
                        }
                        
                        // Add this line to the description
                        $fullDescription .= ' ' . $nextLine;
                        $nextLineIndex++;
                    }
                    
                    // If amounts not found yet, check the line after description
                    if (!$be_2024_25 && $nextLineIndex < count($lines) && 
                        preg_match('/^\d+\.\d{2}$/', trim($lines[$nextLineIndex]))) {
                        $be_2024_25 = trim($lines[$nextLineIndex]);
                        $nextLineIndex++;
                    }
                    
                    if (!$be_2025_26 && $nextLineIndex < count($lines) && 
                        preg_match('/^\d+\.\d{2}$/', trim($lines[$nextLineIndex]))) {
                        $be_2025_26 = trim($lines[$nextLineIndex]);
                        $nextLineIndex++;
                    }
                    
                    // Clean the item description by removing amounts and tabs
                    $cleanItem = preg_replace('/\s*\d+\.\d{2}\s*\d+\.\d{2}.*$/', '', $fullDescription);
                    $cleanItem = preg_replace('/\s*\d+\.\d{2}.*$/', '', $cleanItem);
                    $cleanItem = str_replace("\t", " ", $cleanItem);
                    $cleanItem = trim($cleanItem);
                    
                    $structured[] = [
                        'code' => $code,
                        'item' => $cleanItem,
                        'be_2024_25' => $be_2024_25,
                        'be_2025_26' => $be_2025_26,
                    ];
                    
                    // Update index to skip processed lines
                    $i = $nextLineIndex - 1;
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
                
                // Handle multi-line descriptions for fallback section too
                $fullDescription = $item;
                $nextLineIndex = $i + 1;
                
                // Continue reading next lines until we find amounts or a new budget code
                while ($nextLineIndex < count($lines) && 
                       isset($lines[$nextLineIndex]) && 
                       !preg_match('/^(\d{12,15})-(.+)$/', $lines[$nextLineIndex]) && // Not a new budget code
                       !preg_match('/^\d+\.\d{2}$/', $lines[$nextLineIndex])) { // Not an amount line
                    
                    $nextLine = trim($lines[$nextLineIndex]);
                    
                    // Skip unwanted lines
                    if (stripos($nextLine, 'Details of BE 2025-26 under Demand-01 (DA&FW)') !== false ||
                        stripos($nextLine, 'Rs Lakh') !== false) {
                        $nextLineIndex++;
                        continue;
                    }
                    
                    // If this line contains amounts, extract them and stop
                    if (preg_match_all('/\d+\.\d{2}/', $nextLine, $amounts)) {
                        if (!$be_2024_25 && isset($amounts[0][0])) {
                            $be_2024_25 = $amounts[0][0];
                        }
                        if (!$be_2025_26 && isset($amounts[0][1])) {
                            $be_2025_26 = $amounts[0][1];
                        }
                        break; // Stop reading lines as we found amounts
                    }
                    
                    // Add this line to the description
                    $fullDescription .= ' ' . $nextLine;
                    $nextLineIndex++;
                }
                
                // If amounts not found yet, check the line after description
                if (!$be_2024_25 && $nextLineIndex < count($lines) && 
                    preg_match('/^\d+\.\d{2}$/', trim($lines[$nextLineIndex]))) {
                    $be_2024_25 = trim($lines[$nextLineIndex]);
                    $nextLineIndex++;
                }
                
                if (!$be_2025_26 && $nextLineIndex < count($lines) && 
                    preg_match('/^\d+\.\d{2}$/', trim($lines[$nextLineIndex]))) {
                    $be_2025_26 = trim($lines[$nextLineIndex]);
                    $nextLineIndex++;
                }
                
                // Clean the item description by removing amounts and tabs
                $cleanItem = preg_replace('/\s*\d+\.\d{2}\s*\d+\.\d{2}.*$/', '', $fullDescription);
                $cleanItem = preg_replace('/\s*\d+\.\d{2}.*$/', '', $cleanItem);
                $cleanItem = str_replace("\t", " ", $cleanItem);
                $cleanItem = trim($cleanItem);
                
                $structured[] = [
                    'code' => $code,
                    'item' => $cleanItem,
                    'be_2024_25' => $be_2024_25,
                    'be_2025_26' => $be_2025_26,
                ];
                
                // Update index to skip processed lines
                $i = $nextLineIndex - 1;
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
                // Format the budget head code before saving
                $formattedCode = $this->formatBudgetHeadCode($item['code']);
                
                // Check if budget head already exists
                $existing = BudgetHead::where('budget', $formattedCode)->first();
                
                if (!$existing) {
                    // Calculate category based on budget head code
                    $category = $this->calculateCategory($formattedCode);
                    
                    // Create budget head
                    $budgetHead = BudgetHead::create([
                        'budget' => $formattedCode,
                        'description' => $item['item'],
                        'category' => $category,
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

    /**
     * Calculate category based on budget head code
     * 
     * @param string $code The budget head code
     * @return string The calculated category
     */
    private function calculateCategory($code)
    {
        // Remove any non-digit characters and get the numeric part
        $numericCode = preg_replace('/[^0-9]/', '', $code);
        
        // If code is not long enough, return 'others'
        if (strlen($numericCode) < 9) {
            return 'others';
        }
        
        // Get last 2 digits
        $lastTwoDigits = substr($numericCode, -2);

        // Get second last 2 digits (positions 12-13)
        $secondLastTwoDigits = substr($numericCode, 11, 2);
        
        // Get middle 3 digits (positions 7-9)
        $middleThreeDigits = substr($numericCode, 6, 3);
        
        // If last 2 digits are not "31" or "35", return "Others"
        if ($lastTwoDigits !== '31' && $lastTwoDigits !== '35') {
            return 'Others';
        }

        // if last two digits is '31' and second last two digits is '01' then it will be category of DAJUGA
        if ($lastTwoDigits === '31' && $secondLastTwoDigits == '01') {
            return 'DAJUGA';
        }
        
        // Check middle 3 digits for different categories
        if ($middleThreeDigits === '101' || $middleThreeDigits === '342' || $middleThreeDigits === '103') {
            // If last 2 digits is "35", return "Capital-Gen", else return "Gen"
            return $lastTwoDigits === '35' ? 'Capital-Gen' : 'Gen';
        } elseif ($middleThreeDigits === '789') {
            // If last 2 digits is "35", return "Capital-SC", else return "SC"
            return $lastTwoDigits === '35' ? 'Capital-SC' : 'SC';
        } elseif ($middleThreeDigits === '796') {
            // If last 2 digits is "35", return "Capital-ST", else return "ST"
            return $lastTwoDigits === '35' ? 'Capital-ST' : 'ST';
        }
        
        // Default case
        return 'Others';
    }

    /**
     * Format 15-digit budget head code to the specified format
     * {4 digit}.{2 digit}.{3 digit}.{2 digit}.{2 digit}.{2 digit}
     * Example: 243560103040004 -> 2435.60.103.04.00.04
     */
    private function formatBudgetHeadCode($code)
    {
        // Remove any non-digit characters
        $cleanCode = preg_replace('/[^0-9]/', '', $code);
        
        // If the code is not 15 digits, return as is
        if (strlen($cleanCode) !== 15) {
            return $code;
        }
        
        // Format the 15-digit code into the specified format
        $formatted = substr($cleanCode, 0, 4) . '.' . 
                    substr($cleanCode, 4, 2) . '.' . 
                    substr($cleanCode, 6, 3) . '.' . 
                    substr($cleanCode, 9, 2) . '.' . 
                    substr($cleanCode, 11, 2) . '.' . 
                    substr($cleanCode, 13, 2);
        
        return $formatted;
    }


}
