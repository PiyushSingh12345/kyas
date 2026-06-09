<?php

namespace App\Http\Controllers;

use App\Models\BudgetHead;
use App\Models\BudgetPhase;
use App\Services\SafePdfValidator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BudgetHeadController extends Controller
{
    private const BUDGET_HEAD_PATTERN = '/^(\d{15}|\d{4}\.\d{2}\.\d{3}\.\d{2}\.\d{2}\.\d{2})$/';
    private const DESCRIPTION_PATTERN = "/^[A-Za-z0-9\s\-\.,&()\/']+$/";

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
        $validated = $request->validate(
            [
                'budget' => ['required', 'string', 'max:255', 'regex:' . self::BUDGET_HEAD_PATTERN],
                'description' => ['required', 'string', 'max:255', 'regex:' . self::DESCRIPTION_PATTERN],
                'category' => 'required|in:Gen,SC,ST,Capital-Gen,Capital-SC,Capital-ST,DAJUGA,Others'
            ],
            [
                'budget.regex' => 'Budget Head must be 15 digits or in format 1234.56.789.01.23.45.',
                'description.regex' => 'Head Description contains invalid special characters.',
            ]
        );

        // Format the budget head code before saving
        $formattedBudget = $this->formatBudgetHeadCode($validated['budget']);

        $budgetHead = BudgetHead::create([
            'budget' => $formattedBudget,
            'description' => $validated['description'],
            'category' => $validated['category'],
            'status' => '1',
        ]);

        // Never return JSON to an Inertia form submit; it expects a redirect/Inertia response.
        if (! $request->header('X-Inertia') && $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Budget Head added successfully!',
                'data' => $budgetHead,
                'redirect_url' => url()->previous(),
            ]);
        }

        // Avoid redirect()->back() because Inertia/XHR requests may not include a Referer,
        // which can lead to unexpected fallback redirects.
        return redirect()->route('budget-heads')->with('success', 'Budget Head added successfully!');
    }

    public function destroy(BudgetHead $budgetHead)
    {
        $budgetHead->update(['status' => '0']);

        // Never return JSON to an Inertia request.
        if (! request()->header('X-Inertia') && request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Budget Head deactivated successfully.',
                'data' => $budgetHead->fresh(),
                'redirect_url' => route('budget-heads'),
            ]);
        }

        return redirect()->route('budget-heads')
            ->with('success', 'Budget Head deactivated successfully.');
    }

    public function update(Request $request, BudgetHead $budgetHead)
    {
        $validated = $request->validate(
            [
                'budget' => ['required', 'string', 'max:255', 'regex:' . self::BUDGET_HEAD_PATTERN],
                'description' => ['required', 'string', 'max:255', 'regex:' . self::DESCRIPTION_PATTERN],
                'category' => 'required|in:Gen,SC,ST,Capital-Gen,Capital-SC,Capital-ST,DAJUGA,Others'
            ],
            [
                'budget.regex' => 'Budget Head must be 15 digits or in format 1234.56.789.01.23.45.',
                'description.regex' => 'Head Description contains invalid special characters.',
            ]
        );

        // Format the budget head code before updating
        $formattedBudget = $this->formatBudgetHeadCode($validated['budget']);

        $budgetHead->update([
            'budget' => $formattedBudget,
            'description' => $validated['description'],
            'category' => $validated['category']
        ]);

        // Never return JSON to an Inertia form submit; it expects a redirect/Inertia response.
        if (! $request->header('X-Inertia') && $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Budget Head updated successfully!',
                'data' => $budgetHead->fresh(),
                'redirect_url' => url()->previous(),
            ]);
        }

        return redirect()->route('budget-heads')->with('success', 'Budget Head updated successfully!');
    }

    public function toggleStatus($id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:0,1'
        ]);

        $budgetHead = BudgetHead::findOrFail($id);
        $budgetHead->status = $request->status;
        $budgetHead->save();

        // Never return JSON to an Inertia request.
        if (! $request->header('X-Inertia') && $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully!',
                'data' => $budgetHead->fresh(),
                'redirect_url' => url()->previous(),
            ]);
        }

        return redirect()->route('budget-heads')->with('success', 'Status updated successfully!');
    }

     public function fetchBudgetHeads(Request $request)
    {
        
        $budgetHeads = BudgetHead::where('status', 1)->get();

        return response()->json($budgetHeads);
    }

    /**
     * Fetch budget heads filtered by major head (first 4 digits)
     */
    public function fetchBudgetHeadsByMajorHead(Request $request)
    {
        $request->validate([
            'major_head' => ['required', 'regex:/^\d{4}$/']
        ]);

        $majorHead = $request->query('major_head');
        
        // Get budget heads where the budget code starts with the major head
        // The budget code format is like "2435.60.103.04.00.04", so we check if it starts with the major head
        $budgetHeads = BudgetHead::where('status', 1)
            ->where(function($query) use ($majorHead) {
                $query->where('budget', 'LIKE', $majorHead . '.%')
                      ->orWhere('budget', 'LIKE', $majorHead . '%');
            })
            ->get();

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
                $binary = @file_get_contents($fullPath);
                if ($binary === false) {
                    throw ValidationException::withMessages([
                        'file' => ['Unable to read uploaded PDF.'],
                    ]);
                }
                app(SafePdfValidator::class)->assertSafe($binary, 'file');
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
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
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

    public function uploadTableFormat(Request $request)
    {
        set_time_limit((int) config('budget_head_pdf.ocr_timeout_seconds', 300) + 60);

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();

            $filePath = $file->storeAs('temp', $fileName, 'local');
            $fullPath = Storage::disk('local')->path($filePath);

            $binary = @file_get_contents($fullPath);
            if ($binary === false) {
                throw ValidationException::withMessages([
                    'file' => ['Unable to read uploaded PDF.'],
                ]);
            }
            app(SafePdfValidator::class)->assertSafe($binary, 'file');

            $extractedData = $this->processTableFormatPdfFile($fullPath);
            Storage::disk('local')->delete($filePath);

            if (($extractedData['total_items'] ?? 0) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => $extractedData['error'] ?? 'No budget head data could be extracted from the PDF.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'File processed successfully',
                'data' => $extractedData,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Table format file upload error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'structured_data' => 'required|array',
            'structured_data.*.code' => ['required', 'string', 'regex:' . self::BUDGET_HEAD_PATTERN],
            'structured_data.*.item' => ['required', 'string', 'max:255', 'regex:' . self::DESCRIPTION_PATTERN],
            'structured_data.*.be_2024_25' => 'nullable|string',
            'structured_data.*.be_2025_26' => 'nullable|string',
            'file_name' => 'required|string',
        ], [
            'structured_data.*.code.regex' => 'Each imported budget code must be 15 digits or in format 1234.56.789.01.23.45.',
            'structured_data.*.item.regex' => 'Imported description contains invalid special characters.',
        ]);

        try {
            $result = $this->importStructuredData(
                $request->structured_data,
                $request->file_name,
                fn (array $item) => ! empty($item['be_2025_26'])
                    ? ['financial_year' => '2025-26', 'budget_amount' => (float) $item['be_2025_26']]
                    : null
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$result['imported_count']} new budget heads and {$result['budget_phase_count']} budget phases from {$request->file_name}",
                'imported_count' => $result['imported_count'],
                'budget_phase_count' => $result['budget_phase_count'],
                'total_processed' => $result['total_processed'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function importTableFormat(Request $request)
    {
        $request->validate([
            'structured_data' => 'required|array',
            'structured_data.*.code' => ['required', 'string', 'regex:' . self::BUDGET_HEAD_PATTERN],
            'structured_data.*.item' => ['required', 'string', 'max:255', 'regex:' . self::DESCRIPTION_PATTERN],
            'structured_data.*.budget_amount' => 'nullable',
            'structured_data.*.financial_year' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'financial_years' => 'nullable|array',
            'financial_years.*' => 'string|regex:/^\d{4}-\d{2}$/',
            'file_name' => 'required|string',
        ], [
            'structured_data.*.code.regex' => 'Each imported budget code must be 15 digits or in format 1234.56.789.01.23.45.',
            'structured_data.*.item.regex' => 'Imported description contains invalid special characters.',
        ]);

        try {
            $result = $this->importTableFormatStructuredData(
                $request->structured_data,
                $request->file_name,
                $request->input('financial_years', [])
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$result['imported_count']} new budget heads and {$result['budget_phase_count']} budget phases from {$request->file_name}",
                'imported_count' => $result['imported_count'],
                'budget_phase_count' => $result['budget_phase_count'],
                'total_processed' => $result['total_processed'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing data: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function importTableFormatStructuredData(array $structuredData, string $fileName, array $financialYears = []): array
    {
        $importedCount = 0;
        $budgetPhaseCount = 0;
        $structuredData = $this->backfillTableFormatFinancialYears($structuredData, $financialYears);

        foreach ($structuredData as $item) {
            if (empty($item['financial_year'])) {
                continue;
            }

            $formattedCode = $this->formatBudgetHeadCode($item['code']);
            $existing = BudgetHead::where('budget', $formattedCode)->first();

            if ($existing) {
                $budgetHead = $existing;
            } else {
                $category = $this->calculateCategory($formattedCode);

                $budgetHead = BudgetHead::create([
                    'budget' => $formattedCode,
                    'description' => $item['item'],
                    'category' => $category,
                    'status' => 1,
                ]);
                $importedCount++;
            }

            $amount = $this->normalizeTableFormatAmount($item['budget_amount'] ?? null);
            $budgetAmount = $amount !== null ? (float) $amount : 0.0;

            $phase = BudgetPhase::where('budget_head_id', $budgetHead->id)
                ->where('financial_year', $item['financial_year'])
                ->where('budget_phase', 'BE')
                ->first();

            if ($phase) {
                $phase->update([
                    'budget_amount' => $budgetAmount,
                    'status' => 1,
                    'draft_flag' => 0,
                ]);
            } else {
                BudgetPhase::create([
                    'financial_year' => $item['financial_year'],
                    'budget_phase' => 'BE',
                    'budget_head_id' => $budgetHead->id,
                    'budget_amount' => $budgetAmount,
                    'status' => 1,
                    'draft_flag' => 0,
                ]);
            }

            $budgetPhaseCount++;
        }

        return [
            'imported_count' => $importedCount,
            'budget_phase_count' => $budgetPhaseCount,
            'total_processed' => count($structuredData),
        ];
    }

    private function backfillTableFormatFinancialYears(array $structuredData, array $financialYears): array
    {
        $financialYears = array_values(array_unique(array_filter($financialYears)));

        if (count($financialYears) === 1) {
            $onlyYear = $financialYears[0];
            foreach ($structuredData as &$item) {
                $item['financial_year'] = $onlyYear;
            }
            unset($item);

            return $structuredData;
        }

        $currentYear = $financialYears[0] ?? null;

        foreach ($structuredData as &$item) {
            if (! empty($item['financial_year'])) {
                $currentYear = $item['financial_year'];
                continue;
            }

            if ($currentYear !== null) {
                $item['financial_year'] = $currentYear;
            }
        }
        unset($item);

        return $structuredData;
    }

    private function importStructuredData(array $structuredData, string $fileName, callable $phaseResolver): array
    {
        $importedCount = 0;
        $budgetPhaseCount = 0;

        foreach ($structuredData as $item) {
            $formattedCode = $this->formatBudgetHeadCode($item['code']);
            $existing = BudgetHead::where('budget', $formattedCode)->first();

            if ($existing) {
                continue;
            }

            $category = $this->calculateCategory($formattedCode);

            $budgetHead = BudgetHead::create([
                'budget' => $formattedCode,
                'description' => $item['item'],
                'category' => $category,
                'status' => 1,
            ]);
            $importedCount++;

            $phaseData = $phaseResolver($item);
            if ($phaseData) {
                BudgetPhase::create([
                    'financial_year' => $phaseData['financial_year'],
                    'budget_phase' => 'BE',
                    'budget_head_id' => $budgetHead->id,
                    'budget_amount' => $phaseData['budget_amount'],
                    'status' => 1,
                    'draft_flag' => 0,
                ]);
                $budgetPhaseCount++;
            }
        }

        return [
            'imported_count' => $importedCount,
            'budget_phase_count' => $budgetPhaseCount,
            'total_processed' => count($structuredData),
        ];
    }

    private function processTableFormatPdfFile(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = trim($pdf->getText());

        if ($text !== '') {
            $parsed = $this->parseTableFormatText($text);

            return array_merge(['type' => 'table_pdf'], $this->normalizeTableFormatParsedData($parsed));
        }

        $ocrParsed = $this->runOcrStructuredExtraction($filePath);
        if (($ocrParsed['total_items'] ?? 0) > 0) {
            return array_merge(['type' => 'table_pdf'], $this->normalizeTableFormatParsedData($ocrParsed));
        }

        return [
            'type' => 'table_pdf',
            'error' => $ocrParsed['error'] ?? 'Unable to extract text from PDF. Please upload a searchable PDF export from the budget system, or configure OCR on the server.',
            'structured_data' => [],
            'financial_years' => [],
            'total_items' => 0,
        ];
    }

    private function normalizeTableFormatParsedData(array $parsed): array
    {
        $structured = [];

        foreach ($parsed['structured_data'] ?? [] as $record) {
            $structured[] = [
                'code' => $record['code'],
                'item' => $record['item'],
                'budget_amount' => $this->normalizeTableFormatAmount($record['budget_amount'] ?? null),
                'financial_year' => $record['financial_year'] ?? null,
            ];
        }

        $financialYears = $parsed['financial_years'] ?? [];
        foreach ($structured as $record) {
            if (! empty($record['financial_year']) && ! in_array($record['financial_year'], $financialYears, true)) {
                $financialYears[] = $record['financial_year'];
            }
        }

        $financialYears = array_values(array_unique($financialYears));

        if (count($financialYears) === 1) {
            $onlyYear = $financialYears[0];
            foreach ($structured as &$record) {
                $record['financial_year'] = $onlyYear;
            }
            unset($record);
        }

        return [
            'structured_data' => $structured,
            'financial_years' => array_values($financialYears),
            'total_items' => count($structured),
        ];
    }

    private function runOcrStructuredExtraction(string $filePath): array
    {
        $emptyResult = [
            'structured_data' => [],
            'financial_years' => [],
            'total_items' => 0,
        ];

        $scriptPath = base_path('scripts/extract_budget_head_table_pdf.py');
        if (! file_exists($scriptPath)) {
            return array_merge($emptyResult, [
                'error' => 'OCR script is missing on the server. Please deploy scripts/extract_budget_head_table_pdf.py.',
            ]);
        }

        $pythonBinary = $this->resolvePythonBinary();
        if ($pythonBinary === null) {
            return array_merge($emptyResult, [
                'error' => 'Python is not available on the server. Install Python 3 and set BUDGET_HEAD_PDF_PYTHON in .env, or upload a searchable PDF.',
            ]);
        }

        $processResult = $this->runOcrScript($filePath, $pythonBinary, $scriptPath);
        $output = trim($processResult['stdout'] ?? '');

        if ($output === '') {
            $stderr = trim($processResult['stderr'] ?? '');
            Log::warning('OCR script returned empty output for budget head table PDF.', [
                'file' => $filePath,
                'python' => $pythonBinary,
                'exit_code' => $processResult['exit_code'] ?? null,
                'stderr' => $stderr,
            ]);

            return array_merge($emptyResult, [
                'error' => $this->buildOcrFailureMessage($stderr, $processResult['exit_code'] ?? null),
            ]);
        }

        $decoded = $this->decodeOcrJsonOutput($output);
        if (! is_array($decoded)) {
            Log::warning('OCR script returned invalid JSON for budget head table PDF.', [
                'file' => $filePath,
                'python' => $pythonBinary,
                'output_preview' => substr($output, 0, 500),
                'stderr' => substr($processResult['stderr'] ?? '', 0, 500),
            ]);

            return array_merge($emptyResult, [
                'error' => 'OCR processing failed to return valid data. Please verify Python OCR dependencies are installed on the server.',
            ]);
        }

        if (! empty($decoded['error'])) {
            return array_merge($emptyResult, [
                'error' => 'OCR processing failed: ' . $decoded['error'],
            ]);
        }

        return [
            'structured_data' => $decoded['structured_data'] ?? [],
            'financial_years' => $decoded['financial_years'] ?? [],
            'total_items' => $decoded['total_items'] ?? count($decoded['structured_data'] ?? []),
        ];
    }

    private function resolvePythonBinary(): ?string
    {
        $configured = config('budget_head_pdf.python_binary');
        if (is_string($configured) && $configured !== '' && $this->pythonBinaryExists($configured)) {
            return $configured;
        }

        foreach (['python3', 'python'] as $candidate) {
            if ($this->pythonBinaryExists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function pythonBinaryExists(string $binary): bool
    {
        if (str_contains($binary, DIRECTORY_SEPARATOR) || str_contains($binary, '/')) {
            return is_file($binary) && is_executable($binary);
        }

        $command = PHP_OS_FAMILY === 'Windows'
            ? 'where ' . escapeshellarg($binary)
            : 'command -v ' . escapeshellarg($binary);

        if (! function_exists('shell_exec')) {
            return false;
        }

        $result = trim((string) shell_exec($command . ' 2>/dev/null'));

        return $result !== '';
    }

    private function runOcrScript(string $filePath, string $pythonBinary, string $scriptPath): array
    {
        $timeoutSeconds = (int) config('budget_head_pdf.ocr_timeout_seconds', 300);
        $command = [$pythonBinary, $scriptPath, $filePath];

        putenv('PYTHONIOENCODING=utf-8');
        putenv('PYTHONWARNINGS=ignore');
        putenv('OMP_NUM_THREADS=1');

        if (function_exists('proc_open')) {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = @proc_open($command, $descriptors, $pipes, base_path(), null);
            if (is_resource($process)) {
                fclose($pipes[0]);
                stream_set_blocking($pipes[1], false);
                stream_set_blocking($pipes[2], false);

                $stdout = '';
                $stderr = '';
                $startedAt = time();

                while (true) {
                    $stdout .= stream_get_contents($pipes[1]);
                    $stderr .= stream_get_contents($pipes[2]);
                    $status = proc_get_status($process);

                    if (! $status['running']) {
                        $stdout .= stream_get_contents($pipes[1]);
                        $stderr .= stream_get_contents($pipes[2]);
                        break;
                    }

                    if ((time() - $startedAt) >= $timeoutSeconds) {
                        proc_terminate($process);
                        Log::warning('OCR script timed out for budget head table PDF.', [
                            'file' => $filePath,
                            'timeout_seconds' => $timeoutSeconds,
                        ]);
                        break;
                    }

                    usleep(100000);
                }

                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);

                return [
                    'stdout' => trim($stdout),
                    'stderr' => trim($stderr),
                    'exit_code' => $exitCode,
                ];
            }
        }

        if (! function_exists('shell_exec')) {
            return [
                'stdout' => '',
                'stderr' => 'Process execution functions are disabled on the server.',
                'exit_code' => 1,
            ];
        }

        $envPrefix = PHP_OS_FAMILY === 'Windows' ? '' : 'PYTHONIOENCODING=utf-8 PYTHONWARNINGS=ignore ';
        $commandString = $envPrefix
            . escapeshellarg($pythonBinary) . ' '
            . escapeshellarg($scriptPath) . ' '
            . escapeshellarg($filePath) . ' 2>&1';

        $output = (string) shell_exec($commandString);

        return [
            'stdout' => trim($this->extractJsonPayloadFromOutput($output) ?? $output),
            'stderr' => trim($this->extractNonJsonOutput($output)),
            'exit_code' => 0,
        ];
    }

    private function decodeOcrJsonOutput(string $output): ?array
    {
        $payload = $this->extractJsonPayloadFromOutput($output);
        if ($payload === null) {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function extractJsonPayloadFromOutput(string $output): ?string
    {
        $output = trim($output);
        if ($output === '') {
            return null;
        }

        $decoded = json_decode($output, true);
        if (is_array($decoded)) {
            return $output;
        }

        if (preg_match('/(\{"structured_data".*\})\s*$/s', $output, $matches)) {
            return $matches[1];
        }

        $start = strrpos($output, '{"structured_data"');
        if ($start !== false) {
            $candidate = substr($output, $start);
            if (json_decode($candidate, true) !== null) {
                return $candidate;
            }
        }

        $start = strrpos($output, '{');
        $end = strrpos($output, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($output, $start, $end - $start + 1);
            if (json_decode($candidate, true) !== null) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractNonJsonOutput(string $output): string
    {
        $json = $this->extractJsonPayloadFromOutput($output);
        if ($json === null) {
            return trim($output);
        }

        return trim(str_replace($json, '', $output));
    }

    private function buildOcrFailureMessage(string $stderr, ?int $exitCode): string
    {
        if (str_contains($stderr, 'No module named')) {
            return 'OCR Python packages are missing on the server. Install them with: pip install -r scripts/requirements-budget-head-ocr.txt';
        }

        if (str_contains($stderr, 'Process execution functions are disabled')) {
            return 'The server has disabled process execution functions. Enable proc_open/shell_exec or upload a searchable PDF.';
        }

        if ($exitCode === -1 || str_contains(strtolower($stderr), 'timed out')) {
            return 'OCR processing timed out on the server. Please retry, or upload a searchable PDF export from the budget system.';
        }

        if ($stderr !== '') {
            return 'OCR processing failed on the server: ' . $stderr;
        }

        return 'Unable to extract text from PDF on the server. Install Python OCR dependencies or upload a searchable PDF export from the budget system.';
    }

    private function parseTableFormatText(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text))));
        $structured = [];
        $financialYears = [];
        $currentFinancialYear = null;
        $inKrishonnatiSection = false;
        $foundKrishonnati = false;
        $aboveKrishonnatiBuffer = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            if ($this->isKrishonnatiTotalLine($line)) {
                break;
            }

            $detectedYear = $this->extractFinancialYearFromLine($line);
            if ($detectedYear !== null) {
                $currentFinancialYear = $detectedYear;
                if (! in_array($currentFinancialYear, $financialYears, true)) {
                    $financialYears[] = $currentFinancialYear;
                }
            }

            if ($this->isKrishonnatiHeaderLine($line)) {
                if (! $foundKrishonnati) {
                    $foundKrishonnati = true;
                    $inKrishonnatiSection = true;
                    foreach ($aboveKrishonnatiBuffer as $record) {
                        $structured[] = $record;
                    }
                    $aboveKrishonnatiBuffer = [];
                }

                $remainder = trim(preg_replace('/^Krishonnati\s+Yojna\s*/i', '', $line));
                if ($remainder !== '') {
                    foreach ($this->extractTableFormatRecordsFromSegment($remainder, $currentFinancialYear) as $inlineRecord) {
                        $structured[] = $inlineRecord;
                    }
                }
                continue;
            }

            $record = $this->parseTableFormatBudgetLine($line, $currentFinancialYear);
            if ($record === null) {
                continue;
            }

            if ($record['budget_amount'] === null && isset($lines[$i + 1])) {
                $nextLine = trim($lines[$i + 1]);
                if (preg_match('/^\d+(?:\.\d+)?$/', $nextLine)) {
                    $record['budget_amount'] = $nextLine;
                    $i++;
                }
            }

            $record['budget_amount'] = $this->normalizeTableFormatAmount($record['budget_amount']);

            if ($inKrishonnatiSection) {
                $structured[] = $record;
            } elseif (! $foundKrishonnati && $currentFinancialYear !== null) {
                $aboveKrishonnatiBuffer[] = $record;
            }
        }

        return [
            'structured_data' => $structured,
            'financial_years' => $financialYears,
            'total_items' => count($structured),
        ];
    }

    private function parseTableFormatFromMessyText(string $text): array
    {
        $financialYears = [];
        if (preg_match_all('/BE\s*(\d{4}-\d{2})/i', $text, $yearMatches)) {
            $financialYears = array_values(array_unique($yearMatches[1]));
        }

        $section = $text;
        if (preg_match('/Krishonnati\s+Yojna(.*?)Krishonnati\s+Yojna\s+Total/is', $text, $sectionMatch)) {
            $section = $sectionMatch[1];
        } elseif (preg_match('/Krishonnati\s+Yojna(.*)$/is', $text, $sectionMatch)) {
            $section = preg_replace('/Krishonnati\s+Yojna\s+Total.*$/is', '', $sectionMatch[1]);
        }

        if (preg_match('/Head of account.*?BE\s*(\d{4}-\d{2})(.*?)Krishonnati\s+Yojna/is', $text, $aboveMatch)) {
            $section = $aboveMatch[2] . $section;
            if (! in_array($aboveMatch[1], $financialYears, true)) {
                array_unshift($financialYears, $aboveMatch[1]);
            }
        }

        $defaultFinancialYear = $financialYears[0] ?? null;
        $segments = preg_split('/BE\s*(\d{4}-\d{2})/i', $section, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $structured = [];

        if (count($segments) === 1) {
            $structured = $this->extractTableFormatRecordsFromSegment($segments[0], $defaultFinancialYear);
        } else {
            $segmentIndex = 0;
            while ($segmentIndex < count($segments)) {
                $segment = $segments[$segmentIndex];
                if (preg_match('/^\d{4}-\d{2}$/', trim($segment))) {
                    $year = trim($segment);
                    $content = $segments[$segmentIndex + 1] ?? '';
                    $structured = array_merge($structured, $this->extractTableFormatRecordsFromSegment($content, $year));
                    $segmentIndex += 2;
                    continue;
                }

                $structured = array_merge($structured, $this->extractTableFormatRecordsFromSegment($segment, $defaultFinancialYear));
                $segmentIndex++;
            }
        }

        $structured = $this->deduplicateTableFormatRecords($structured);

        return [
            'structured_data' => $structured,
            'financial_years' => $financialYears,
            'total_items' => count($structured),
        ];
    }

    private function extractTableFormatRecordsFromSegment(string $segment, ?string $financialYear): array
    {
        $normalized = preg_replace('/[_\|]/', '-', $segment);
        preg_match_all(
            '/(\d{15})(?:[-\s]+)([A-Za-z][A-Za-z0-9\s\-.,&()\/\']*?)(?=\d{15}|BE\s*\d{4}-\d{2}|Krishonnati\s+Yojna\s+Total|$)/i',
            $normalized,
            $matches,
            PREG_SET_ORDER
        );

        $records = [];
        $orphanAmounts = [];

        foreach ($matches as $match) {
            $item = trim(preg_replace('/\s+/', ' ', $match[2]), " \t\n\r\0\x0B-");
            if ($item === '' || stripos($item, 'Head of account') !== false) {
                continue;
            }

            $amount = null;
            if (preg_match('/^(.+?)\s+(\d+(?:\.\d+)?)\s*$/', $item, $amountMatch)) {
                $item = trim($amountMatch[1], " \t\n\r\0\x0B-");
                $amount = $amountMatch[2];
            }

            if (preg_match('/^(\d+(?:\.\d+)?(?:\s+\d+(?:\.\d+)?)+)\s*$/', $item)) {
                $orphanAmounts = array_merge($orphanAmounts, preg_split('/\s+/', trim($item)));
                continue;
            }

            $records[] = [
                'code' => $match[1],
                'item' => $item,
                'budget_amount' => $amount,
                'financial_year' => $financialYear,
            ];
        }

        if (preg_match_all('/(?<!\d)(\d{1,9}(?:\.\d+)?)(?!\d)/', $segment, $numberMatches)) {
            $orphanAmounts = array_merge($orphanAmounts, $numberMatches[1]);
        }

        $amountIndex = 0;
        foreach ($records as &$record) {
            if ($record['budget_amount'] === null && isset($orphanAmounts[$amountIndex])) {
                $record['budget_amount'] = $orphanAmounts[$amountIndex];
                $amountIndex++;
            }
        }
        unset($record);

        return $records;
    }

    private function deduplicateTableFormatRecords(array $records): array
    {
        $unique = [];

        foreach ($records as $record) {
            $key = $record['code'] . '|' . ($record['financial_year'] ?? '');
            if (! isset($unique[$key])) {
                $unique[$key] = $record;
            }
        }

        return array_values($unique);
    }

    private function extractFinancialYearFromLine(string $line): ?string
    {
        if (preg_match('/Head of account.*?BE\s*(\d{4}-\d{2})/i', $line, $matches)) {
            return $matches[1];
        }

        if (stripos($line, 'Head of account') !== false && preg_match('/BE\s*(\d{4}-\d{2})/i', $line, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^BE\s*(\d{4}-\d{2})$/i', trim($line), $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function isKrishonnatiHeaderLine(string $line): bool
    {
        $trimmed = trim($line);

        if (preg_match('/Krishonnati\s+Yojna\s+Total/i', $trimmed)) {
            return false;
        }

        if (preg_match('/^Krishonnati\s+Yojna\s*$/i', $trimmed)) {
            return true;
        }

        return (bool) preg_match('/^Krishonnati\s+Yojna\b/i', $trimmed);
    }

    private function isKrishonnatiTotalLine(string $line): bool
    {
        return (bool) preg_match('/Krishonnati\s+Yojna\s+Total/i', $line);
    }

    private function parseTableFormatBudgetLine(string $line, ?string $financialYear): ?array
    {
        if (! preg_match('/^(\d{12,15})-(.+)$/', $line, $parts)) {
            return null;
        }

        $code = trim($parts[1]);
        $rest = trim($parts[2]);
        $amount = null;
        $item = $rest;

        if (preg_match('/^(.+?)\s+(\d+(?:\.\d+)?)\s*$/', $rest, $amountMatch)) {
            $item = trim($amountMatch[1]);
            $amount = $this->normalizeTableFormatAmount($amountMatch[2]);
        }

        $item = trim(preg_replace('/\s+/', ' ', $item));
        if ($item === '' || stripos($item, 'Head of account') !== false) {
            return null;
        }

        return [
            'code' => $code,
            'item' => $item,
            'budget_amount' => $amount,
            'financial_year' => $financialYear,
        ];
    }

    private function normalizeTableFormatAmount(mixed $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $cleanAmount = preg_replace('/[^\d.]/', '', (string) $amount);
        if ($cleanAmount === '') {
            return null;
        }

        if (! str_contains($cleanAmount, '.') && strlen($cleanAmount) >= 4) {
            $cleanAmount = $this->correctOcrAmountDigits($cleanAmount);
        }

        if ($cleanAmount === '' || (float) $cleanAmount === 0.0) {
            return null;
        }

        return $cleanAmount;
    }

    private function correctOcrAmountDigits(string $digits): string
    {
        if (str_ends_with($digits, '1') && strlen($digits) >= 4 && $digits[strlen($digits) - 2] === '0') {
            return substr($digits, 0, -1);
        }

        return $digits;
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
