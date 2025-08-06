<?php 
namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\SlsPDComponent;
use App\Models\State;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class SlsPDComponentController extends Controller
{

    public function index()
    {
        $data = SlsPDComponent::with('state')->get();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'component' => 'required|in:PD,SL',
            'comValue' => 'required|array',
            'comValue.*.state' => 'required|integer',
            'comValue.*.name' => 'required|string',
            'comValue.*.slsPD' => 'nullable|string',
            'status' => 'required|in:0,1'
        ]);

        foreach ($validated['comValue'] as $entry) {
            SlsPDComponent::create([
                'component' => $validated['component'],
                'state_id' => $entry['state'],
                'slsPD' => $entry['slsPD'] ?? null,
                'name' => $entry['name'],
                'status' => $validated['status']
            ]);
        }

        return redirect()->back()->with('success', 'Data stored successfully!');
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240' // 10MB max
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            Log::info('Excel file loaded', [
                'filename' => $file->getClientOriginalName(),
                'total_rows' => count($rows),
                'first_few_rows' => array_slice($rows, 0, 5)
            ]);

            // Find the actual data rows by looking for the header row
            $dataStartRow = 0;
            $headers = [];
            $columnMapping = [];
            
            // Look for the header row that contains our expected columns
            foreach ($rows as $rowIndex => $row) {
                $rowString = implode(' ', array_filter($row, 'is_string'));
                $rowStringLower = strtolower($rowString);
                
                // Check for the specific header pattern found in the analysis
                if (strpos($rowStringLower, 'controller') !== false && 
                    strpos($rowStringLower, 'centrally sponsored scheme') !== false &&
                    strpos($rowStringLower, 'state linked scheme') !== false) {
                    $headers = $row;
                    $dataStartRow = $rowIndex + 1;
                    
                    // Map columns based on the exact structure found
                    $columnMapping = [
                        'controller' => 0,
                        'css' => 1,
                        'cgAccount' => 2,
                        'stateName' => 3,
                        'sls' => 4,
                        'sgAccount' => 6, // Column 7 (0-indexed)
                        'sgAccountStatus' => 7, // Column 8 (0-indexed)
                        'sharingPatternCentre' => 8, // Column 9 (0-indexed)
                        'sharingPatternState' => 9, // Column 10 (0-indexed)
                        'sharingPatternStatus' => 10, // Column 11 (0-indexed)
                        'topUpApplicable' => 11 // Column 12 (0-indexed)
                    ];
                    
                    Log::info('Header row found', [
                        'row_index' => $rowIndex,
                        'headers' => $headers,
                        'data_start_row' => $dataStartRow,
                        'column_mapping' => $columnMapping
                    ]);
                    break;
                }
            }

            if ($dataStartRow === 0) {
                // Try to find data rows directly by looking for SLS code patterns
                Log::warning('Header row not found, trying to parse data directly');
                $dataStartRow = 0;
                $parseResult = $this->parseDataWithoutHeader($rows);
                
                if (empty($parseResult['data'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not find the expected header row or valid SLS data. Please ensure the Excel file contains columns: Controller, Centrally Sponsored Scheme (CSS), State Linked Scheme (SLS), State Name, SG Account, Sharing Pattern'
                    ], 400);
                }
                
                return response()->json($parseResult);
            }

            // Extract data rows starting after the header
            $dataRows = array_slice($rows, $dataStartRow);
            
            $parsedData = [];

            foreach ($dataRows as $index => $row) {
                // Skip empty rows
                if (empty(array_filter($row, function($cell) { return !empty(trim($cell)); }))) {
                    continue;
                }

                // Extract data based on column mapping
                $slsData = $this->extractSLSData($row, $columnMapping);
                
                if (!$slsData) {
                    continue;
                }

                // Skip rows that don't look like SLS data (e.g., header rows, summary rows)
                if (strpos(strtolower($slsData['slsCode']), 'sls') !== false || 
                    strpos(strtolower($slsData['slsCode']), 'code') !== false ||
                    strpos(strtolower($slsData['slsCode']), 'total') !== false ||
                    strpos(strtolower($slsData['slsCode']), 'subtotal') !== false) {
                    continue;
                }

                // Find state ID by name (with fallback)
                $state = State::where('name', 'LIKE', '%' . $slsData['stateName'] . '%')->first();
                $stateId = $state ? $state->id : 1; // Default to state ID 1 if not found

                $parsedData[] = [
                    'slsCode' => $slsData['slsCode'],
                    'slsName' => $slsData['slsName'],
                    'stateName' => $slsData['stateName'],
                    'stateId' => $stateId,
                    'sgAccount' => $slsData['sgAccount'],
                    'sharingPatternCentre' => $slsData['sharingPatternCentre'],
                    'sharingPatternState' => $slsData['sharingPatternState'],
                    'controller' => $slsData['controller'],
                    'css' => $slsData['css'],
                    'cgAccount' => $slsData['cgAccount'],
                    'sgAccountStatus' => $slsData['sgAccountStatus'],
                    'sharingPatternStatus' => $slsData['sharingPatternStatus'],
                    'topUpApplicable' => $slsData['topUpApplicable']
                ];
            }

            Log::info('Excel parsing completed successfully', [
                'parsed_records' => count($parsedData),
                'sample_data' => array_slice($parsedData, 0, 3)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Excel file parsed successfully',
                'data' => $parsedData,
                'totalRows' => count($parsedData)
            ]);

        } catch (\Exception $e) {
            Log::error('Excel upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing Excel file: ' . $e->getMessage()
            ], 500);
        }
    }

    private function mapColumns($headers)
    {
        $mapping = [];
        
        foreach ($headers as $index => $header) {
            $headerLower = strtolower(trim($header));
            
            if (strpos($headerLower, 'state linked scheme') !== false || strpos($headerLower, 'sls') !== false) {
                $mapping['sls'] = $index;
            } elseif (strpos($headerLower, 'state name') !== false || strpos($headerLower, 'state') !== false) {
                $mapping['stateName'] = $index;
            } elseif (strpos($headerLower, 'sg account') !== false || strpos($headerLower, 'account') !== false) {
                $mapping['sgAccount'] = $index;
            } elseif (strpos($headerLower, 'sharing pattern') !== false) {
                if (strpos($headerLower, 'centre') !== false || strpos($headerLower, 'center') !== false) {
                    $mapping['sharingPatternCentre'] = $index;
                } elseif (strpos($headerLower, 'state') !== false) {
                    $mapping['sharingPatternState'] = $index;
                }
            } elseif (strpos($headerLower, 'controller') !== false) {
                $mapping['controller'] = $index;
            } elseif (strpos($headerLower, 'centrally sponsored scheme') !== false || strpos($headerLower, 'css') !== false) {
                $mapping['css'] = $index;
            } elseif (strpos($headerLower, 'cg account') !== false) {
                $mapping['cgAccount'] = $index;
            } elseif (strpos($headerLower, 'sg account status') !== false) {
                $mapping['sgAccountStatus'] = $index;
            } elseif (strpos($headerLower, 'sharing pattern status') !== false) {
                $mapping['sharingPatternStatus'] = $index;
            } elseif (strpos($headerLower, 'top-up') !== false || strpos($headerLower, 'top up') !== false) {
                $mapping['topUpApplicable'] = $index;
            }
        }
        
        return $mapping;
    }

    private function extractSLSData($row, $columnMapping)
    {
        // Extract SLS data from the State Linked Scheme (SLS) column (Column 5)
        $slsColumn = $columnMapping['sls'] ?? 4;
        $slsData = trim($row[$slsColumn] ?? '');
        
        if (empty($slsData)) {
            return null;
        }
        
        // Split by ' - ' to get SLS Code and SLS Name (note the space-dash-space format)
        $parts = explode(' - ', $slsData, 2);
        if (count($parts) < 2) {
            // Try with just dash if space-dash-space doesn't work
            $parts = explode('-', $slsData, 2);
            if (count($parts) < 2) {
                // If still can't split, use the whole string as SLS Name and generate a code
                $slsCode = 'SLS' . rand(100, 999);
                $slsName = $slsData;
            } else {
                $slsCode = trim($parts[0]);
                $slsName = trim($parts[1]);
            }
        } else {
            $slsCode = trim($parts[0]);
            $slsName = trim($parts[1]);
        }
        
        return [
            'slsCode' => $slsCode,
            'slsName' => $slsName,
            'stateName' => trim($row[$columnMapping['stateName'] ?? 3] ?? ''),
            'sgAccount' => trim($row[$columnMapping['sgAccount'] ?? 6] ?? ''),
            'sharingPatternCentre' => trim($row[$columnMapping['sharingPatternCentre'] ?? 8] ?? ''),
            'sharingPatternState' => trim($row[$columnMapping['sharingPatternState'] ?? 9] ?? ''),
            'controller' => trim($row[$columnMapping['controller'] ?? 0] ?? ''),
            'css' => trim($row[$columnMapping['css'] ?? 1] ?? ''),
            'cgAccount' => trim($row[$columnMapping['cgAccount'] ?? 2] ?? ''),
            'sgAccountStatus' => trim($row[$columnMapping['sgAccountStatus'] ?? 7] ?? ''),
            'sharingPatternStatus' => trim($row[$columnMapping['sharingPatternStatus'] ?? 10] ?? ''),
            'topUpApplicable' => trim($row[$columnMapping['topUpApplicable'] ?? 11] ?? '')
        ];
    }

    private function parseDataWithoutHeader($rows)
    {
        $dataStartRow = 0;
        $parsedData = [];
        $errors = [];

        // Look for the first row that contains SLS code patterns
        foreach ($rows as $rowIndex => $row) {
            $rowString = implode(' ', array_filter($row, 'is_string'));
            $rowStringLower = strtolower($rowString);

            if (strpos($rowStringLower, 'controller') !== false || 
                strpos($rowStringLower, 'centrally sponsored scheme') !== false ||
                strpos($rowStringLower, 'state linked scheme') !== false) {
                $dataStartRow = $rowIndex + 1;
                break;
            }
        }

        if ($dataStartRow === 0) {
            return [
                'success' => false,
                'message' => 'No SLS data found in Excel file',
                'errors' => ['Could not find Controller, Centrally Sponsored Scheme (CSS), or State Linked Scheme (SLS) columns'],
                'data' => [],
                'totalRows' => 0
            ];
        }

        // Extract data rows starting after the first SLS code row
        $dataRows = array_slice($rows, $dataStartRow);

        foreach ($dataRows as $index => $row) {
            // Skip empty rows
            if (empty(array_filter($row, function($cell) { return !empty(trim($cell)); }))) {
                continue;
            }

            // Try to extract SLS data from the State Linked Scheme column (Column 5)
            $slsData = trim($row[4] ?? ''); // Column 5 (0-indexed)
            
            if (empty($slsData)) {
                continue;
            }
            
            // Split by ' - ' to get SLS Code and SLS Name (note the space-dash-space format)
            $parts = explode(' - ', $slsData, 2);
            if (count($parts) < 2) {
                // Try with just dash if space-dash-space doesn't work
                $parts = explode('-', $slsData, 2);
                if (count($parts) < 2) {
                    continue;
                }
            }
            
            $slsCode = trim($parts[0]);
            $slsName = trim($parts[1]);
            $stateName = trim($row[3] ?? ''); // State Name column (Column 4)
            $sgAccount = trim($row[6] ?? ''); // SG Account column (Column 7)
            $sharingPatternCentre = trim($row[8] ?? ''); // Centre column of Sharing Pattern (Column 9)
            $sharingPatternState = trim($row[9] ?? ''); // State column of Sharing Pattern (Column 10)
            $controller = trim($row[0] ?? ''); // Controller column (Column 1)
            $css = trim($row[1] ?? ''); // CSS column (Column 2)
            $cgAccount = trim($row[2] ?? ''); // CG Account column (Column 3)
            $sgAccountStatus = trim($row[7] ?? ''); // SG Account Status column (Column 8)
            $sharingPatternStatus = trim($row[10] ?? ''); // Sharing Pattern Status column (Column 11)
            $topUpApplicable = trim($row[11] ?? ''); // Is Top-Up applicable column (Column 12)

            // Skip rows that don't look like SLS data
            if (strpos(strtolower($slsCode), 'sls') !== false || 
                strpos(strtolower($slsCode), 'code') !== false ||
                strpos(strtolower($slsCode), 'total') !== false ||
                strpos(strtolower($slsCode), 'subtotal') !== false) {
                continue;
            }

            // Find state ID by name (with fallback)
            $state = State::where('name', 'LIKE', '%' . $stateName . '%')->first();
            $stateId = $state ? $state->id : 1; // Default to state ID 1 if not found

            $parsedData[] = [
                'slsCode' => $slsCode,
                'slsName' => $slsName,
                'stateName' => $stateName,
                'stateId' => $stateId,
                'sgAccount' => $sgAccount,
                'sharingPatternCentre' => $sharingPatternCentre,
                'sharingPatternState' => $sharingPatternState,
                'controller' => $controller,
                'css' => $css,
                'cgAccount' => $cgAccount,
                'sgAccountStatus' => $sgAccountStatus,
                'sharingPatternStatus' => $sharingPatternStatus,
                'topUpApplicable' => $topUpApplicable
            ];
        }

        return [
            'success' => true,
            'message' => 'Excel file parsed successfully (fallback method)',
            'data' => $parsedData,
            'totalRows' => count($parsedData)
        ];
    }

    public function saveSLSData(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'data.*.slsCode' => 'required|string',
            'data.*.slsName' => 'required|string',
            'data.*.stateId' => 'required|integer|exists:states,id',
            'data.*.sgAccount' => 'nullable|string',
            'data.*.sharingPatternCentre' => 'nullable|string',
            'data.*.sharingPatternState' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $savedCount = 0;
            $errors = [];

            foreach ($request->data as $item) {
                try {
                    // Check if SLS already exists
                    $existingSLS = SlsPDComponent::where('name', $item['slsCode'])
                        ->where('component', 'SL')
                        ->where('state_id', $item['stateId'])
                        ->first();

                    if ($existingSLS) {
                        $errors[] = "SLS Code '{$item['slsCode']}' already exists for this state";
                        continue;
                    }

                    // Create new SLS record
                    SlsPDComponent::create([
                        'component' => 'SL',
                        'state_id' => $item['stateId'],
                        'slsPD' => null, // This will be set when mapping with PD
                        'name' => $item['slsCode'], // Store SLS Code as name
                        'status' => 1
                    ]);

                    $savedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Error saving SLS '{$item['slsCode']}': " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Some records could not be saved',
                    'errors' => $errors,
                    'savedCount' => $savedCount
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully saved {$savedCount} SLS records",
                'savedCount' => $savedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SLS data save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getComponentsByFund(Request $request)
    {
        $fund = $request->get('fund');
        $stateId = $request->get('state_id'); // Get state_id if provided

        if ($fund == '2435') {
            $components = SlsPDComponent::with('state')
                ->where('component', 'PD')
                ->get();
        } elseif (in_array($fund, ['3601', '3602', '2552'])) {
            if (!$stateId) {
                return response()->json(['error' => 'State ID required for this fund'], 422);
            }

            $components = SlsPDComponent::with('state')
                ->where('component', 'SL')
                ->where('state_id', $stateId)
                ->get();
        } else {
            return response()->json([], 400);
        }

        return response()->json($components);
    }

    public function destroy($id)
    {
        try {
            $slsComponent = SlsPDComponent::findOrFail($id);
            $slsComponent->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting record: ' . $e->getMessage()
            ], 500);
        }
    }
}

