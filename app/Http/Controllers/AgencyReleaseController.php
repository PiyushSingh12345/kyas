<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\AgencyReleaseTSA;
use App\Models\AgencyReleaseLOA;
use App\Models\AgencyReleaseAdministrativeExpenditure;

class AgencyReleaseController extends Controller
{
    /**
     * Store TSA form data
     */
    public function storeTSA(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sanctionNumber' => 'required|string|max:255',
                'date' => 'required|date',
                'budgetHead' => 'required|string|max:255',
                'purposeOfGrant' => 'required|string',
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'centralImplementingAgency' => 'required|string|max:255',
            ]);

            $tsa = AgencyReleaseTSA::create([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'central_implementing_agency' => $validated['centralImplementingAgency'],
                'status' => 1
            ]);

            Log::info('TSA record created successfully', ['id' => $tsa->id]);

            return response()->json([
                'success' => true,
                'message' => 'TSA data saved successfully',
                'data' => $tsa
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing TSA data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save TSA data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store LOA form data
     */
    public function storeLOA(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sanctionNumber' => 'required|string|max:255',
                'date' => 'required|date',
                'budgetHead' => 'required|string|max:255',
                'purposeOfGrant' => 'required|string',
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'ut' => 'required|string|max:255',
            ]);

            $loa = AgencyReleaseLOA::create([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'ut' => $validated['ut'],
                'status' => 1
            ]);

            Log::info('LOA record created successfully', ['id' => $loa->id]);

            return response()->json([
                'success' => true,
                'message' => 'LOA data saved successfully',
                'data' => $loa
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing LOA data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save LOA data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store Administrative Expenditure form data
     */
    public function storeAdministrativeExpenditure(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sanctionNumber' => 'required|string|max:255',
                'date' => 'required|date',
                'budgetHead' => 'required|string|max:255',
                'purposeOfGrant' => 'required|string',
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'agencyVendor' => 'required|string|max:255',
            ]);

            $adminExp = AgencyReleaseAdministrativeExpenditure::create([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'agency_vendor' => $validated['agencyVendor'],
                'status' => 1
            ]);

            Log::info('Administrative Expenditure record created successfully', ['id' => $adminExp->id]);

            return response()->json([
                'success' => true,
                'message' => 'Administrative Expenditure data saved successfully',
                'data' => $adminExp
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing Administrative Expenditure data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save Administrative Expenditure data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of TSA records
     */
    public function listTSA(): JsonResponse
    {
        try {
            $tsaRecords = AgencyReleaseTSA::with('programDivision')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'sanction_number' => $record->sanction_number,
                        'date' => $record->date,
                        'budget_head' => $record->budget_head,
                        'purpose_of_grant' => $record->purpose_of_grant,
                        'program_division' => $record->programDivision->division_name ?? '',
                        'amount' => $record->amount,
                        'central_implementing_agency' => $record->central_implementing_agency,
                        'status' => $record->status,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                });

            return response()->json($tsaRecords);
        } catch (\Exception $e) {
            Log::error('Error fetching TSA list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch TSA data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of LOA records
     */
    public function listLOA(): JsonResponse
    {
        try {
            $loaRecords = AgencyReleaseLOA::with('programDivision')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'sanction_number' => $record->sanction_number,
                        'date' => $record->date,
                        'budget_head' => $record->budget_head,
                        'purpose_of_grant' => $record->purpose_of_grant,
                        'program_division' => $record->programDivision->division_name ?? '',
                        'amount' => $record->amount,
                        'ut' => $record->ut,
                        'status' => $record->status,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                });

            return response()->json($loaRecords);
        } catch (\Exception $e) {
            Log::error('Error fetching LOA list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch LOA data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of Administrative Expenditure records
     */
    public function listAdministrativeExpenditure(): JsonResponse
    {
        try {
            $adminExpRecords = AgencyReleaseAdministrativeExpenditure::with('programDivision')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'sanction_number' => $record->sanction_number,
                        'date' => $record->date,
                        'budget_head' => $record->budget_head,
                        'purpose_of_grant' => $record->purpose_of_grant,
                        'program_division' => $record->programDivision->division_name ?? '',
                        'amount' => $record->amount,
                        'agency_vendor' => $record->agency_vendor,
                        'status' => $record->status,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                });

            return response()->json($adminExpRecords);
        } catch (\Exception $e) {
            Log::error('Error fetching Administrative Expenditure list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Administrative Expenditure data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status for Agency Release records (Close/Revise)
     */
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer',
                'type' => 'required|in:tsa,loa,administrative-expenditure',
                'action' => 'required|in:close,revise'
            ]);

            $id = $validated['id'];
            $type = $validated['type'];
            $action = $validated['action'];

            if ($type === 'tsa') {
                $record = AgencyReleaseTSA::findOrFail($id);
            } elseif ($type === 'loa') {
                $record = AgencyReleaseLOA::findOrFail($id);
            } else {
                $record = AgencyReleaseAdministrativeExpenditure::findOrFail($id);
            }

            if ($action === 'close') {
                // Close: Set status to inactive
                $record->status = 0;
                $record->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Record closed successfully',
                    'data' => $record
                ]);
            } else {
                // Revise: Set old record to inactive
                $record->status = 0;
                $record->save();

                // Return record data for prefilling form
                $recordData = [
                    'id' => $record->id,
                    'sanction_number' => $record->sanction_number,
                    'date' => $record->date ? $record->date->format('Y-m-d') : '',
                    'budget_head' => $record->budget_head,
                    'purpose_of_grant' => $record->purpose_of_grant,
                    'program_division_id' => $record->program_division_id,
                    'amount' => $record->amount,
                ];

                if ($type === 'tsa') {
                    $recordData['central_implementing_agency'] = $record->central_implementing_agency;
                } elseif ($type === 'loa') {
                    $recordData['ut'] = $record->ut;
                } else {
                    $recordData['agency_vendor'] = $record->agency_vendor;
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Record marked for revision. Redirecting to form.',
                    'data' => $recordData
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating Agency Release status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update record: ' . $e->getMessage()
            ], 500);
        }
    }
}


