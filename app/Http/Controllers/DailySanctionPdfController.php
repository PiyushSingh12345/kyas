<?php

namespace App\Http\Controllers;

use App\Services\SafePdfValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use App\Models\State;
use App\Models\MotherSanction;
use Illuminate\Validation\ValidationException;

class DailySanctionPdfController extends Controller
{
    public function processPdf(Request $request)
    {
        try {
            $request->validate([
                'pdf_file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            ]);

            $file = $request->file('pdf_file');
            $binary = @file_get_contents($file->getRealPath());
            if ($binary === false) {
                throw ValidationException::withMessages([
                    'pdf_file' => ['Unable to read uploaded PDF.'],
                ]);
            }
            app(SafePdfValidator::class)->assertSafe($binary, 'pdf_file');

            $parser = new Parser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();

            Log::info('PDF Text extracted', ['text_length' => strlen($text)]);

            // Extract data from PDF text
            $extractedData = $this->extractDataFromPdf($text);

            Log::info('PDF Data extracted', ['extracted_data' => $extractedData]);

            return response()->json([
                'success' => true,
                'data' => $extractedData,
                'message' => 'PDF processed successfully',
                'debug' => [
                    'text_length' => strlen($text),
                    'text_preview' => substr($text, 0, 500) // First 500 characters for debugging
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Processing Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    // private function extractDataFromPdf($text)
    // {
    //     $data = [
    //         'financial_year' => '',
    //         'state_name' => '',
    //         'state_id' => null,
    //         'ds_date' => '',
    //         'daily_sanction_no' => '',
    //         'mother_sanction' => '',
    //         'ifd_no' => '',
    //         'sls_name' => '',
    //         'remark' => '',
    //         'sanction_details' => []
    //     ];

    //     // Extract Financial Year - look for patterns like 2025-26, 2025-2026
    //     if (preg_match('/Financial Year[:\s]*(\d{4}-\d{2,4})/i', $text, $matches)) {
    //         $fy = $matches[1];
    //         // Convert 2025-26 to 2025-2026 format
    //         if (strlen($fy) === 7) { // 2025-26 format
    //             $year1 = substr($fy, 0, 4);
    //             $year2 = '20' . substr($fy, 5, 2);
    //             $data['financial_year'] = $year1 . '-' . $year2;
    //         } else {
    //             $data['financial_year'] = $fy;
    //         }
    //     }

    //     // Extract State Name (look for Jammu and Kashmir)
    //     if (preg_match('/Jammu\s+and\s+Kashmir/i', $text, $matches)) {
    //         $data['state_name'] = 'Jammu and Kashmir';
    //         // Find state ID from database
    //         $state = State::where('name', 'LIKE', '%Jammu%Kashmir%')->first();
    //         if ($state) {
    //             $data['state_id'] = $state->id;
    //         }
    //     }

    //     // Extract DS Date - look for various date patterns
    //     $datePatterns = [
    //         '/DS\s+Date[:\s]*(\d{1,2}[.\/\-]\d{1,2}[.\/\-]\d{4})/i',
    //         '/Date[:\s]*(\d{1,2}[.\/\-]\d{1,2}[.\/\-]\d{4})/i',
    //         '/(\d{1,2}[.\/\-]\d{1,2}[.\/\-]\d{4})/i'
    //     ];
        
    //     foreach ($datePatterns as $pattern) {
    //         if (preg_match($pattern, $text, $matches)) {
    //             $dateStr = $matches[1];
    //             $parsedDate = $this->parseDate($dateStr);
    //             if ($parsedDate) {
    //                 $data['ds_date'] = $parsedDate;
    //                 break;
    //             }
    //         }
    //     }

    //     // Extract Daily Sanction Number - look for JK 277 pattern
    //     if (preg_match('/Daily\s+Sanction\s+No[:\s]*([A-Z0-9\s\-]+)/i', $text, $matches)) {
    //         $data['daily_sanction_no'] = trim($matches[1]);
    //     } elseif (preg_match('/JK\s*277/i', $text, $matches)) {
    //         $data['daily_sanction_no'] = $matches;
    //     }

    //     // Extract Mother Sanction - look for various patterns (more specific)
    //     $motherSanctionPatterns = [
    //         '/Mother\s+Sanction[:\s]*([A-Z0-9\-]+)/i',
    //         '/KY-MS[:\s]*([A-Z0-9\-]+)/i',
    //         '/Mother\s+Sanction\s+No[:\s]*([A-Z0-9\-]+)/i',
    //         '/MS\s+No[:\s]*([A-Z0-9\-]+)/i',
    //         '/Sanction\s+No[:\s]*([A-Z0-9\-]+)/i'
    //     ];
        
    //     foreach ($motherSanctionPatterns as $pattern) {
    //         if (preg_match($pattern, $text, $matches)) {
    //             $sanction = trim($matches[1]);
    //             // Validate that it looks like a sanction number (alphanumeric with dashes)
    //             if (preg_match('/^[A-Z0-9\-]+$/', $sanction) && strlen($sanction) >= 3) {
    //                 $data['mother_sanction'] = $sanction;
    //                 // If it doesn't start with KY-MS, add the prefix
    //                 if (!preg_match('/^KY-MS/i', $data['mother_sanction'])) {
    //                     $data['mother_sanction'] = 'KY-MS-' . $data['mother_sanction'];
    //                 }
    //                 break;
    //             }
    //         }
    //     }

    //     // Extract IFD Number - look for various patterns (more specific)
    //     $ifdPatterns = [
    //         '/IFD\s+No[:\s]*([A-Z0-9\-]+)/i',
    //         '/IFD[:\s]*([A-Z0-9\-]+)/i',
    //         '/IFD\s+Number[:\s]*([A-Z0-9\-]+)/i',
    //         '/File\s+No[:\s]*([A-Z0-9\-]+)/i',
    //         '/Reference\s+No[:\s]*([A-Z0-9\-]+)/i'
    //     ];
        
    //     foreach ($ifdPatterns as $pattern) {
    //         if (preg_match($pattern, $text, $matches)) {
    //             $ifd = trim($matches[1]);
    //             // Validate that it looks like an IFD number (alphanumeric with dashes)
    //             if (preg_match('/^[A-Z0-9\-]+$/', $ifd) && strlen($ifd) >= 3) {
    //                 // $data['ifd_no'] = $ifd;
    //                 $data['ifd_no'] = $matches;
    //                 break;
    //             }
    //         }
    //     }

    //     // Extract SLS Name - look for various patterns (more specific)
    //     $slsPatterns = [
    //         '/SLS\s+Name[:\s]*([A-Za-z\s]+)/i',
    //         '/Scheme\s+Name[:\s]*([A-Za-z\s]+)/i',
    //         '/Program\s+Name[:\s]*([A-Za-z\s]+)/i',
    //         '/SLS[:\s]*([A-Za-z\s]+)/i'
    //     ];
        
    //     foreach ($slsPatterns as $pattern) {
    //         if (preg_match($pattern, $text, $matches)) {
    //             $slsName = trim($matches[1]);
    //             // Clean up the extracted name - remove common prefixes/suffixes
    //             $slsName = preg_replace('/^(Name|Scheme|Programme|SLS|Program)[:\s]*/i', '', $slsName);
    //             $slsName = preg_replace('/[:\s]*$/', '', $slsName);
                
    //             // Only use if it's a reasonable length and doesn't contain common unwanted words
    //             if (strlen($slsName) > 5 && strlen($slsName) < 80 && 
    //                 !preg_match('/^(Name|Scheme|Programme|SLS|Program|No|Number|BE|Final|Expenditure)$/i', $slsName) &&
    //                 !preg_match('/\d{4}-\d{2,4}/', $slsName)) { // Exclude financial year patterns
    //                 $data['sls_name'] = $slsName;
    //                 break;
    //             }
    //         }
    //     }

    //     // Extract Remark
    //     if (preg_match('/Remark[:\s]*([^\n\r]+)/i', $text, $matches)) {
    //         $data['remark'] = trim($matches[1]);
    //     }

    //     // Extract sanction details from table-like structure
    //     $data['sanction_details'] = $this->extractSanctionDetails($text);

    //     return $data;
    // }

    // private function extractDataFromPdf($text)
    // {
    //     $data = [
    //         'financial_year' => '',
    //         'state_name' => '',
    //         'state_id' => null,
    //         'ds_date' => '',
    //         'daily_sanction_no' => [],
    //         'mother_sanction' => '',
    //         'ifd_no' => [],
    //         'sls_name' => '',
    //         'remark' => '',
    //         'sanction_details' => []
    //     ];

    //     // Financial Year
    //     if (preg_match('/Financial Year[:\s]*(\d{4}-\d{2,4})/i', $text, $m)) {
    //         $fy = $m[1];
    //         if (strlen($fy) === 7) $fy = substr($fy, 0, 4) . '-20' . substr($fy, 5, 2);
    //         $data['financial_year'] = $fy;
    //     }

    //     // State
    //     if (preg_match('/State\s*[:\s]+JAMMU\s+AND\s+KASHMIR/i', $text)) {
    //         $data['state_name'] = 'Jammu and Kashmir';
    //         $state = State::where('name', 'LIKE', '%Jammu%Kashmir%')->first();
    //         $data['state_id'] = $state ? $state->id : null;
    //     }

    //     // DS Date
    //     if (preg_match('/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})/', $text, $m)) {
    //         $data['ds_date'] = $this->parseDate($m[1]);
    //     }

    //     // IFD Number (Full + Numeric)
    //     if (preg_match('/IFD\s*Number\s*[:\s]*([0-9_()\sA-Z-]+)/i', $text, $m)) {
    //         $fullIfd = trim($m[1]);
    //         if (preg_match('/\b(\d{3,})\b/', $fullIfd, $num)) {
    //             $data['ifd_no'] = [$fullIfd, $num[1]];
    //         } else {
    //             $data['ifd_no'] = [$fullIfd];
    //         }
    //     }

    //     // Extract Scheme Code (for daily sanction no)
    //     if (preg_match('/Scheme\s*[:\s]*([0-9]{3,5})\s*-\s*([A-Za-z\s]+)/i', $text, $m)) {
    //         $schemeCode = trim($m[1]);
    //         $schemeName = trim($m[2]);
    //         $data['daily_sanction_no'] = [$schemeCode];
    //         $data['sls_name'] = "JK277 -NationalBambooMission(KRISHIONNATIYOJANA[{$schemeCode}])";
    //     }

    //     // Extract IFD Table Data
    //     if (preg_match('/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})\s+([0-9,]+)\s+([0-9,]+)\s+([0-9,]+)/', $text, $m)) {
    //         $ifdAmt = (float)str_replace(',', '', $m[2]);          // IFD Amount
    //         $motherBal = (float)str_replace(',', '', $m[3]);       // Mother sanction balance booked
    //         $motherDisb = (float)str_replace(',', '', $m[4]);      // Mother sanction balance disbursed
    //         $data['mother_sanction'] = $motherDisb;
    //     }

    //     // Extract File Name and Center Share Amount
    //     if (preg_match('/(EPV\d+)/i', $text, $m)) {
    //         $fileName = $m[1];
    //     } else {
    //         $fileName = '';
    //     }

    //     if (preg_match('/\b([0-9,]+)\b\s*0\.00\s*\(0\.00%\)\s*([0-9,]+)/', $text, $m)) {
    //         $centerShare = (float)str_replace(',', '', $m[1]);
    //     } else {
    //         $centerShare = 55000; // fallback from example
    //     }

    //     // Build sanction_details
    //     $data['sanction_details'][] = [
    //         'budget_head' => '2025',
    //         'mother_sanction_amount' => 4394750,
    //         'available_fund' => 4561500,
    //         'center_share_amount' => $centerShare,
    //         'file_name' => $fileName
    //     ];

    //     return $data;
    // }

    private function extractDataFromPdf($text)
    {
        $data = [
            'financial_year' => '',
            'state_name' => '',
            'state_id' => null,
            'ds_date' => '',
            'daily_sanction_no' => [],
            'mother_sanction' => '',
            'ifd_no' => [],
            'sls_name' => '',
            'remark' => '',
            'sanction_details' => []
        ];

        // --- Financial Year ---
        if (preg_match('/Financial\s*Year\s*[:\s]+(\d{4}-\d{2,4})/i', $text, $m)) {
            $fy = $m[1];
            if (strlen($fy) === 7) {
                $fy = substr($fy, 0, 4) . '-20' . substr($fy, 5, 2);
            }
            $data['financial_year'] = $fy;
        }

        // --- State Name ---
        if (preg_match('/State\s*[:\s]+([A-Z\s]+)/i', $text, $m)) {
            $stateName = trim($m[1]);
            // Clean up the state name - remove everything after newline or unwanted text
            $stateName = preg_replace('/\n.*$/', '', $stateName);
            $stateName = preg_replace('/Ifd\s+Number.*$/i', '', $stateName);
            $stateName = trim($stateName);
            $data['state_name'] = ucwords(strtolower($stateName));
            
            $state = State::where('name', 'LIKE', "%$stateName%")->first();
            $data['state_id'] = $state ? $state->id : null;
        }

        // --- DS Date ---
        if (preg_match('/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})/', $text, $m)) {
            $data['ds_date'] = $this->parseDate($m[1]);
        }

        // --- IFD Number (Full + Numeric) ---
        if (preg_match('/IFD\s*Number\s*[:\s]*([0-9_()\sA-Z-]+)/i', $text, $m)) {
            $ifdFull = trim($m[1]);
            // Clean up the IFD number - remove unwanted text after newline
            $ifdFull = preg_replace('/\n.*$/', '', $ifdFull);
            $ifdFull = preg_replace('/IFD\s+Details.*$/i', '', $ifdFull);
            $ifdFull = trim($ifdFull);
            
            // Extract just the numeric part
            if (preg_match('/\b(\d{3,})\b/', $ifdFull, $num)) {
                $data['ifd_no'] = ["IFD Number : " . $ifdFull, $num[1]];
            } else {
                $data['ifd_no'] = ["IFD Number : " . $ifdFull];
            }
        }

        // --- Extract Scheme Info (for daily_sanction_no and sls_name) ---
        // Matches: Scheme :  4138 - KRISHIONNATI YOJANA
        if (preg_match('/Scheme\s*[:\s]*([0-9]{3,6})\s*[-–]\s*([A-Za-z\s]+)/i', $text, $m)) {
            $schemeCode = trim($m[1]);
            $schemeName = trim(preg_replace('/\s+/', ' ', $m[2]));
            $data['daily_sanction_no'] = [$schemeCode];
        } else {
            $schemeCode = '';
            $schemeName = '';
        }

        // --- Extract IFD Details Table ---
        // Example: 155_279 (JK 277) 17/07/2025 4561500 4394750 4394750
        $availableFund = 0;
        $motherSanctionAmount = 0;

        if (preg_match('/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})\s+([0-9,]+)\s+([0-9,]+)\s+([0-9,]+)/', $text, $m)) {
            $availableFund = (float)str_replace(',', '', $m[2]);   // IFD Amount
            $motherSanctionAmount = (float)str_replace(',', '', $m[3]); // Mother sanction booked
            $data['mother_sanction'] = $motherSanctionAmount;
        }

        // --- Extract File Name (EPV...) and Center Share Amount ---
        $fileName = '';
        $centerShareAmount = 0;

        if (preg_match('/(EPV[0-9]+)/i', $text, $m)) {
            $fileName = trim($m[1]);
        }

        // Capture center share amount line e.g. "55000 (100.00%)"
        if (preg_match('/\b([0-9,]+)\b\s*\(100\.00%\)/', $text, $m)) {
            $centerShareAmount = (float)str_replace(',', '', $m[1]);
        } else {
            // Try alternative pattern for center share amount
            if (preg_match('/Center\s+Share[:\s]*([0-9,]+)/i', $text, $m)) {
                $centerShareAmount = (float)str_replace(',', '', $m[1]);
            } elseif (preg_match('/Daily\s+Sanction[:\s]*([0-9,]+)/i', $text, $m)) {
                $centerShareAmount = (float)str_replace(',', '', $m[1]);
            }
        }

        // --- Dynamically Build SLS Name ---
        // Try to capture lines like: "JK277 - National Bamboo Mission (KRISHIONNATI YOJANA [4138])"
        if (preg_match('/([A-Z0-9]+)\s*[-–]\s*([A-Za-z\s]+)\(([^)]+)\[([0-9]+)\]\)/i', $text, $m)) {
            // Full match from formatted text
            $data['sls_name'] = trim($m[0]);
        } else {
            // Construct dynamically if not found in one line
            $ifdCodePart = '';
            if (preg_match('/\(([A-Z0-9\s]+)\)/i', $data['ifd_no'][0] ?? '', $m)) {
                $ifdCodePart = strtoupper(str_replace(' ', '', $m[1]));
            }

            if ($ifdCodePart && $schemeName && $schemeCode) {
                $data['sls_name'] = "{$ifdCodePart} - {$schemeName} ({$schemeName} [{$schemeCode}])";
            } elseif ($schemeName && $schemeCode) {
                $data['sls_name'] = "{$schemeName} [{$schemeCode}]";
            } else {
                $data['sls_name'] = '';
            }
        }
        
        // Clean up SLS name - remove newlines and extra spaces
        $data['sls_name'] = preg_replace('/\s+/', ' ', $data['sls_name']);
        $data['sls_name'] = trim($data['sls_name']);

        // --- Build Sanction Details ---
        $data['sanction_details'][] = [
            'budget_head' => substr($data['financial_year'], 0, 4), // e.g. 2025
            'mother_sanction_amount' => $motherSanctionAmount,
            'available_fund' => $availableFund,
            'center_share_amount' => $centerShareAmount,
            'file_name' => $fileName
        ];

        return $data;
    }


    
    private function parseDate($dateStr)
    {
        // Handle various date formats
        $formats = [
            'd.m.Y', 'd/m/Y', 'd-m-Y',
            'd.m.y', 'd/m/y', 'd-m-y',
            'Y-m-d', 'Y/m/d', 'Y.m.d'
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return '';
    }

    private function extractSanctionDetails($text)
    {
        $details = [];
        
        // Split text into lines for better processing
        $lines = preg_split('/\r\n|\r|\n/', $text);
        
        $inTableSection = false;
        $tableStartPatterns = [
            '/Budget\s+Head/i',
            '/Mother\s+Sanctioned/i',
            '/Available/i',
            '/Daily\s+Sanction/i',
            '/Center\s+Share/i'
        ];
        
        foreach ($lines as $lineIndex => $line) {
            $line = trim($line);
            
            // Detect table start
            foreach ($tableStartPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $inTableSection = true;
                    break;
                }
            }
            
            if ($inTableSection && !empty($line)) {
                // Look for rows with budget head information
                // Pattern: Budget Head followed by numbers (amounts)
                if (preg_match('/^([A-Z0-9\s\-\.]+)\s+([0-9,]+\.?\d*)\s+([0-9,]+\.?\d*)\s+([0-9,]+\.?\d*)/', $line, $matches)) {
                    $budgetHead = trim($matches[1]);
                    $motherAmount = (float) str_replace(',', '', $matches[2]);
                    $availableAmount = (float) str_replace(',', '', $matches[3]);
                    $centerShareAmount = (float) str_replace(',', '', $matches[4]);
                    
                    // Skip if budget head looks like a header
                    if (!preg_match('/Budget|Mother|Available|Daily|Center|Head|Amount/i', $budgetHead)) {
                        $details[] = [
                            'budget_head' => $budgetHead,
                            'mother_sanction_amount' => $motherAmount,
                            'available_fund' => $availableAmount,
                            'center_share_amount' => $centerShareAmount
                        ];
                    }
                }
                // Alternative pattern for different table formats
                elseif (preg_match('/^([A-Z0-9\s\-\.]+)\s+([0-9,]+\.?\d*)\s+([0-9,]+\.?\d*)/', $line, $matches)) {
                    $budgetHead = trim($matches[1]);
                    $motherAmount = (float) str_replace(',', '', $matches[2]);
                    $availableAmount = (float) str_replace(',', '', $matches[3]);
                    
                    if (!preg_match('/Budget|Mother|Available|Daily|Center|Head|Amount/i', $budgetHead)) {
                        $details[] = [
                            'budget_head' => $budgetHead,
                            'mother_sanction_amount' => $motherAmount,
                            'available_fund' => $availableAmount,
                            'center_share_amount' => 0 // Default value
                        ];
                    }
                }
            }
        }
        
        // If no details found with table parsing, try to extract from the entire text
        if (empty($details)) {
            $details = $this->extractDetailsFromText($text);
        }
        
        return $details;
    }

    private function extractDetailsFromText($text)
    {
        $details = [];
        
        // Look for budget head patterns in the entire text
        $patterns = [
            // Pattern 1: Budget Head followed by amounts
            '/([A-Z0-9\s\-\.]+)\s+([0-9,]+\.?\d*)\s+([0-9,]+\.?\d*)\s+([0-9,]+\.?\d*)/',
            // Pattern 2: Budget Head followed by two amounts
            '/([A-Z0-9\s\-\.]+)\s+([0-9,]+\.?\d*)\s+([0-9,]+\.?\d*)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $budgetHead = trim($match[1]);
                    
                    // Skip if it looks like a header or invalid budget head
                    if (!preg_match('/Budget|Mother|Available|Daily|Center|Head|Amount|Total|Sub/i', $budgetHead) 
                        && strlen($budgetHead) > 3) {
                        
                        $motherAmount = (float) str_replace(',', '', $match[2]);
                        $availableAmount = (float) str_replace(',', '', $match[3]);
                        $centerShareAmount = isset($match[4]) ? (float) str_replace(',', '', $match[4]) : 0;
                        
                        $details[] = [
                            'budget_head' => $budgetHead,
                            'mother_sanction_amount' => $motherAmount,
                            'available_fund' => $availableAmount,
                            'center_share_amount' => $centerShareAmount
                        ];
                    }
                }
                break; // Use first pattern that finds matches
            }
        }
        
        return $details;
    }

    public function getMotherSanctionsByState(Request $request)
    {
        try {
            $stateId = $request->get('state_id');
            
            if (!$stateId) {
                return response()->json([
                    'success' => false,
                    'message' => 'State ID is required'
                ], 400);
            }

            $motherSanctions = MotherSanction::where('state_id', $stateId)
                ->where('status', 1)
                ->select('ky_ms_no', 'ifd_no', 'sls_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $motherSanctions
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching mother sanctions', [
                'error' => $e->getMessage(),
                'state_id' => $request->get('state_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mother sanctions'
            ], 500);
        }
    }
}
