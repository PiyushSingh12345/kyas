<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\AgencyReleaseTSA;
use App\Models\AgencyReleaseLOA;
use App\Models\AgencyReleaseAdministrativeExpenditure;
use App\Models\AgencyReleaseHistory;
use App\Models\BudgetPhase;
use App\Models\BudgetHead;

class AgencyReleaseController extends Controller
{
    private const SAFE_TEXT_PATTERN = "/^[A-Za-z0-9\s\-\.,&()\/:'_]+$/";
    private const SAFE_BUDGET_HEAD_PATTERN = '/^(\d{15}|\d{4}\.\d{2}\.\d{3}\.\d{2}\.\d{2}\.\d{2})$/';

    /**
     * Sum active releases for a budget head + program division, optionally excluding one record.
     */
    private function sumReleasesForBudgetAndPd(
        string $budgetHead,
        int $programDivisionId,
        ?string $excludeType = null,
        ?int $excludeId = null
    ): float {
        $tsaQuery = AgencyReleaseTSA::where('budget_head', $budgetHead)
            ->where('program_division_id', $programDivisionId)
            ->where('status', 1);
        if ($excludeType === 'tsa' && $excludeId) {
            $tsaQuery->where('id', '!=', $excludeId);
        }

        $loaQuery = AgencyReleaseLOA::where('budget_head', $budgetHead)
            ->where('program_division_id', $programDivisionId)
            ->where('status', 1);
        if ($excludeType === 'loa' && $excludeId) {
            $loaQuery->where('id', '!=', $excludeId);
        }

        $adminExpQuery = AgencyReleaseAdministrativeExpenditure::where('budget_head', $budgetHead)
            ->where('program_division_id', $programDivisionId)
            ->where('status', 1);
        if ($excludeType === 'administrative-expenditure' && $excludeId) {
            $adminExpQuery->where('id', '!=', $excludeId);
        }

        return $tsaQuery->sum('amount')
            + $loaQuery->sum('amount')
            + $adminExpQuery->sum('amount');
    }

    /**
     * Validate amount against balanced fund; returns error message or null if valid.
     */
    private function validateAmountAgainstBalancedFund(
        string $budgetHead,
        int $programDivisionId,
        float $amount,
        ?string $excludeType = null,
        ?int $excludeId = null
    ): ?string {
        $budgetHeadRecord = BudgetHead::where('budget', $budgetHead)->first();

        if (!$budgetHeadRecord) {
            return 'Invalid budget head';
        }

        $allocatedAmount = DB::table('pdwise_aap_allocation')
            ->where('bh_id', $budgetHeadRecord->id)
            ->where('pd_id', $programDivisionId)
            ->where('status', 1)
            ->sum('amount');

        $totalReleases = $this->sumReleasesForBudgetAndPd(
            $budgetHead,
            $programDivisionId,
            $excludeType,
            $excludeId
        );

        $balancedFundAmount = $allocatedAmount - $totalReleases;

        if ($amount > $balancedFundAmount) {
            return "Amount (₹{$amount} lakhs) cannot exceed Balanced Fund Amount (₹{$balancedFundAmount} lakhs)";
        }

        return null;
    }

    /**
     * Store TSA form data
     */
    public function storeTSA(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sanctionNumber' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'date' => 'required|date',
                'budgetHead' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_BUDGET_HEAD_PATTERN],
                'purposeOfGrant' => ['required', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'expenditure' => 'nullable|numeric|min:0|lte:amount',
                'centralImplementingAgency' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'isNer' => 'sometimes|boolean',
                'remark' => 'sometimes|nullable|string|max:1000',
            ], [
                'sanctionNumber.regex' => 'Sanction number contains invalid special characters.',
                'budgetHead.regex' => 'Budget head format is invalid.',
                'purposeOfGrant.regex' => 'Purpose of grant contains invalid special characters.',
                'centralImplementingAgency.regex' => 'Agency name contains invalid special characters.',
                'expenditure.lte' => 'Expenditure cannot exceed Amount.',
            ]);

            // Get budget head record
            $budgetHeadRecord = BudgetHead::where('budget', $validated['budgetHead'])->first();
            
            if (!$budgetHeadRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid budget head'
                ], 422);
            }

            // Calculate balanced fund amount for specific budget head + program division
            $allocatedAmount = DB::table('pdwise_aap_allocation')
                ->where('bh_id', $budgetHeadRecord->id)
                ->where('pd_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');

            // Sum releases from ALL THREE tables for this budget head + program division
            $tsaReleases = AgencyReleaseTSA::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $loaReleases = AgencyReleaseLOA::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $adminExpReleases = AgencyReleaseAdministrativeExpenditure::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $totalReleases = $tsaReleases + $loaReleases + $adminExpReleases;
            $balancedFundAmount = $allocatedAmount - $totalReleases;

            // Check if amount exceeds balanced fund amount
            if ($validated['amount'] > $balancedFundAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount (₹{$validated['amount']} lakhs) cannot exceed Balanced Fund Amount (₹{$balancedFundAmount} lakhs)"
                ], 422);
            }

            $tsa = AgencyReleaseTSA::create([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'expenditure' => $validated['expenditure'] ?? null,
                'central_implementing_agency' => $validated['centralImplementingAgency'],
                'is_ner' => $request->boolean('isNer'),
                'remark' => $validated['remark'] ?? null,
                'status' => 1
            ]);

            $this->saveAgencyReleaseHistory('tsa', $tsa, 'CREATE', 'TSA record created');

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
     * Update TSA record
     */
    public function updateTSA(Request $request, int $id): JsonResponse
    {
        try {
            $tsa = AgencyReleaseTSA::findOrFail($id);

            $validated = $request->validate([
                'sanctionNumber' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'date' => 'required|date',
                'budgetHead' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_BUDGET_HEAD_PATTERN],
                'purposeOfGrant' => ['required', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'expenditure' => 'nullable|numeric|min:0|lte:amount',
                'centralImplementingAgency' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'isNer' => 'sometimes|boolean',
                'remark' => 'sometimes|nullable|string|max:1000',
            ], [
                'sanctionNumber.regex' => 'Sanction number contains invalid special characters.',
                'budgetHead.regex' => 'Budget head format is invalid.',
                'purposeOfGrant.regex' => 'Purpose of grant contains invalid special characters.',
                'centralImplementingAgency.regex' => 'Agency name contains invalid special characters.',
                'expenditure.lte' => 'Expenditure cannot exceed Amount.',
            ]);

            $balanceError = $this->validateAmountAgainstBalancedFund(
                $validated['budgetHead'],
                (int) $validated['programDivision'],
                (float) $validated['amount'],
                'tsa',
                $id
            );

            if ($balanceError) {
                return response()->json([
                    'success' => false,
                    'message' => $balanceError,
                ], 422);
            }

            $tsa->update([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'expenditure' => $validated['expenditure'] ?? null,
                'central_implementing_agency' => $validated['centralImplementingAgency'],
                'is_ner' => $request->boolean('isNer'),
                'remark' => $validated['remark'] ?? null,
            ]);

            $this->saveAgencyReleaseHistory('tsa', $tsa, 'UPDATE', 'TSA record updated');

            return response()->json([
                'success' => true,
                'message' => 'TSA record updated successfully',
                'data' => $tsa,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating TSA data', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update TSA data: ' . $e->getMessage(),
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
                'sanctionNumber' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'date' => 'required|date',
                'budgetHead' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_BUDGET_HEAD_PATTERN],
                'purposeOfGrant' => ['required', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'ut' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'remark' => 'sometimes|nullable|string|max:1000',
            ], [
                'sanctionNumber.regex' => 'Sanction number contains invalid special characters.',
                'budgetHead.regex' => 'Budget head format is invalid.',
                'purposeOfGrant.regex' => 'Purpose of grant contains invalid special characters.',
                'ut.regex' => 'UT contains invalid special characters.',
            ]);

            // Get budget head record
            $budgetHeadRecord = BudgetHead::where('budget', $validated['budgetHead'])->first();
            
            if (!$budgetHeadRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid budget head'
                ], 422);
            }

            // Calculate balanced fund amount for specific budget head + program division
            $allocatedAmount = DB::table('pdwise_aap_allocation')
                ->where('bh_id', $budgetHeadRecord->id)
                ->where('pd_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');

            // Sum releases from ALL THREE tables for this budget head + program division
            $tsaReleases = AgencyReleaseTSA::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $loaReleases = AgencyReleaseLOA::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $adminExpReleases = AgencyReleaseAdministrativeExpenditure::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $totalReleases = $tsaReleases + $loaReleases + $adminExpReleases;
            $balancedFundAmount = $allocatedAmount - $totalReleases;

            // Check if amount exceeds balanced fund amount
            if ($validated['amount'] > $balancedFundAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount (₹{$validated['amount']} lakhs) cannot exceed Balanced Fund Amount (₹{$balancedFundAmount} lakhs)"
                ], 422);
            }

            $loa = AgencyReleaseLOA::create([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'ut' => $validated['ut'],
                'remark' => $validated['remark'] ?? null,
                'status' => 1
            ]);

            $this->saveAgencyReleaseHistory('loa', $loa, 'CREATE', 'LOA record created');

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
     * Update LOA record
     */
    public function updateLOA(Request $request, int $id): JsonResponse
    {
        try {
            $loa = AgencyReleaseLOA::findOrFail($id);

            $validated = $request->validate([
                'sanctionNumber' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'date' => 'required|date',
                'budgetHead' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_BUDGET_HEAD_PATTERN],
                'purposeOfGrant' => ['required', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'ut' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'remark' => 'sometimes|nullable|string|max:1000',
            ], [
                'sanctionNumber.regex' => 'Sanction number contains invalid special characters.',
                'budgetHead.regex' => 'Budget head format is invalid.',
                'purposeOfGrant.regex' => 'Purpose of grant contains invalid special characters.',
                'ut.regex' => 'UT contains invalid special characters.',
            ]);

            $balanceError = $this->validateAmountAgainstBalancedFund(
                $validated['budgetHead'],
                (int) $validated['programDivision'],
                (float) $validated['amount'],
                'loa',
                $id
            );

            if ($balanceError) {
                return response()->json([
                    'success' => false,
                    'message' => $balanceError,
                ], 422);
            }

            $loa->update([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'ut' => $validated['ut'],
                'remark' => $validated['remark'] ?? null,
            ]);

            $this->saveAgencyReleaseHistory('loa', $loa, 'UPDATE', 'LOA record updated');

            return response()->json([
                'success' => true,
                'message' => 'LOA record updated successfully',
                'data' => $loa,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating LOA data', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update LOA data: ' . $e->getMessage(),
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
                'sanctionNumber' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'date' => 'required|date',
                'budgetHead' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_BUDGET_HEAD_PATTERN],
                'purposeOfGrant' => ['required', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'agencyVendor' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'remark' => 'sometimes|nullable|string|max:1000',
            ], [
                'sanctionNumber.regex' => 'Sanction number contains invalid special characters.',
                'budgetHead.regex' => 'Budget head format is invalid.',
                'purposeOfGrant.regex' => 'Purpose of grant contains invalid special characters.',
                'agencyVendor.regex' => 'Agency/vendor contains invalid special characters.',
            ]);

            // Get budget head record
            $budgetHeadRecord = BudgetHead::where('budget', $validated['budgetHead'])->first();
            
            if (!$budgetHeadRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid budget head'
                ], 422);
            }

            // Calculate balanced fund amount for specific budget head + program division
            $allocatedAmount = DB::table('pdwise_aap_allocation')
                ->where('bh_id', $budgetHeadRecord->id)
                ->where('pd_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');

            // Sum releases from ALL THREE tables for this budget head + program division
            $tsaReleases = AgencyReleaseTSA::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $loaReleases = AgencyReleaseLOA::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $adminExpReleases = AgencyReleaseAdministrativeExpenditure::where('budget_head', $validated['budgetHead'])
                ->where('program_division_id', $validated['programDivision'])
                ->where('status', 1)
                ->sum('amount');
            
            $totalReleases = $tsaReleases + $loaReleases + $adminExpReleases;
            $balancedFundAmount = $allocatedAmount - $totalReleases;

            // Check if amount exceeds balanced fund amount
            if ($validated['amount'] > $balancedFundAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount (₹{$validated['amount']} lakhs) cannot exceed Balanced Fund Amount (₹{$balancedFundAmount} lakhs)"
                ], 422);
            }

            $adminExp = AgencyReleaseAdministrativeExpenditure::create([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'agency_vendor' => $validated['agencyVendor'],
                'remark' => $validated['remark'] ?? null,
                'status' => 1
            ]);

            $this->saveAgencyReleaseHistory('administrative-expenditure', $adminExp, 'CREATE', 'Administrative Expenditure record created');

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
     * Update Administrative Expenditure record
     */
    public function updateAdministrativeExpenditure(Request $request, int $id): JsonResponse
    {
        try {
            $adminExp = AgencyReleaseAdministrativeExpenditure::findOrFail($id);

            $validated = $request->validate([
                'sanctionNumber' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'date' => 'required|date',
                'budgetHead' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_BUDGET_HEAD_PATTERN],
                'purposeOfGrant' => ['required', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
                'programDivision' => 'required|integer|exists:md_program_divisions,division_id',
                'amount' => 'required|numeric|min:0',
                'agencyVendor' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'remark' => 'sometimes|nullable|string|max:1000',
            ], [
                'sanctionNumber.regex' => 'Sanction number contains invalid special characters.',
                'budgetHead.regex' => 'Budget head format is invalid.',
                'purposeOfGrant.regex' => 'Purpose of grant contains invalid special characters.',
                'agencyVendor.regex' => 'Agency/vendor contains invalid special characters.',
            ]);

            $balanceError = $this->validateAmountAgainstBalancedFund(
                $validated['budgetHead'],
                (int) $validated['programDivision'],
                (float) $validated['amount'],
                'administrative-expenditure',
                $id
            );

            if ($balanceError) {
                return response()->json([
                    'success' => false,
                    'message' => $balanceError,
                ], 422);
            }

            $adminExp->update([
                'sanction_number' => $validated['sanctionNumber'],
                'date' => $validated['date'],
                'budget_head' => $validated['budgetHead'],
                'purpose_of_grant' => $validated['purposeOfGrant'],
                'program_division_id' => $validated['programDivision'],
                'amount' => $validated['amount'],
                'agency_vendor' => $validated['agencyVendor'],
                'remark' => $validated['remark'] ?? null,
            ]);

            $this->saveAgencyReleaseHistory('administrative-expenditure', $adminExp, 'UPDATE', 'Administrative Expenditure record updated');

            return response()->json([
                'success' => true,
                'message' => 'Administrative Expenditure record updated successfully',
                'data' => $adminExp,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating Administrative Expenditure data', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Administrative Expenditure data: ' . $e->getMessage(),
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
                        'date' => $record->date?->format('Y-m-d') ?? $record->date,
                        'budget_head' => $record->budget_head,
                        'purpose_of_grant' => $record->purpose_of_grant,
                        'program_division' => $record->programDivision->division_name ?? '',
                        'program_division_id' => $record->program_division_id,
                        'amount' => $record->amount,
                        'expenditure' => $record->expenditure,
                        'central_implementing_agency' => $record->central_implementing_agency,
                        'is_ner' => $record->is_ner,
                        'remark' => $record->remark,
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
                        'date' => $record->date?->format('Y-m-d') ?? $record->date,
                        'budget_head' => $record->budget_head,
                        'purpose_of_grant' => $record->purpose_of_grant,
                        'program_division' => $record->programDivision->division_name ?? '',
                        'program_division_id' => $record->program_division_id,
                        'amount' => $record->amount,
                        'ut' => $record->ut,
                        'remark' => $record->remark,
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
                        'date' => $record->date?->format('Y-m-d') ?? $record->date,
                        'budget_head' => $record->budget_head,
                        'purpose_of_grant' => $record->purpose_of_grant,
                        'program_division' => $record->programDivision->division_name ?? '',
                        'program_division_id' => $record->program_division_id,
                        'amount' => $record->amount,
                        'agency_vendor' => $record->agency_vendor,
                        'remark' => $record->remark,
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

                $this->saveAgencyReleaseHistory($type, $record, 'CLOSE', 'Record closed');

                return response()->json([
                    'success' => true,
                    'message' => 'Record closed successfully',
                    'data' => $record
                ]);
            } else {
                // Revise: Set old record to inactive
                $record->status = 0;
                $record->save();

                $this->saveAgencyReleaseHistory($type, $record, 'REVISE', 'Record marked for revision');

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
                    $recordData['expenditure'] = $record->expenditure;
                    $recordData['is_ner'] = $record->is_ner;
                    $recordData['remark'] = $record->remark;
                } elseif ($type === 'loa') {
                    $recordData['ut'] = $record->ut;
                    $recordData['remark'] = $record->remark;
                } else {
                    $recordData['agency_vendor'] = $record->agency_vendor;
                    $recordData['remark'] = $record->remark;
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

    /**
     * Soft delete Agency Release records
     */
    public function softDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer',
                'type' => 'required|in:tsa,loa,administrative-expenditure',
            ]);

            $id = $validated['id'];
            $type = $validated['type'];

            if ($type === 'tsa') {
                $record = AgencyReleaseTSA::findOrFail($id);
            } elseif ($type === 'loa') {
                $record = AgencyReleaseLOA::findOrFail($id);
            } else {
                $record = AgencyReleaseAdministrativeExpenditure::findOrFail($id);
            }

            $record->status = 0;
            $record->save();

            $this->saveAgencyReleaseHistory($type, $record, 'DELETE', 'Record soft deleted');

            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error soft deleting Agency Release record', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get balanced fund amount for a budget head (TSA)
     * When both budget_head and program_division are provided:
     *   Returns: Amount allocated for that Budget head and Program Division - 
     *            (sum of ALL amounts from TSA + LOA + Admin Exp for that Budget head and Program Division)
     */
    public function getBalancedFundAmount(Request $request): JsonResponse
    {
        try {
            $budgetHead = $request->input('budget_head');
            $programDivisionId = $request->input('program_division_id');

            if (!$budgetHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget head is required'
                ], 400);
            }

            // Get the budget head ID from the budget string (e.g., "2435.60.103.04.00.09")
            $budgetHeadRecord = BudgetHead::where('budget', $budgetHead)->first();

            if (!$budgetHeadRecord) {
                Log::warning('Budget head not found', ['budget_head' => $budgetHead]);
                return response()->json([
                    'allocated_amount' => 0,
                    'total_releases' => 0
                ]);
            }

            // Calculate allocated amount based on whether program division is selected
            $allocatedAmount = 0;
            if ($programDivisionId) {
                // Get allocation for specific budget head + program division
                $allocatedAmount = DB::table('pdwise_aap_allocation')
                    ->where('bh_id', $budgetHeadRecord->id)
                    ->where('pd_id', $programDivisionId)
                    ->where('status', 1)
                    ->sum('amount');
                
                Log::info('PD-specific allocation fetched', [
                    'budget_head' => $budgetHead,
                    'program_division_id' => $programDivisionId,
                    'allocated_amount' => $allocatedAmount
                ]);
            } else {
                // Get the sum of ALL Program Divisions' allocations for this budget head
                $allocatedAmount = DB::table('pdwise_aap_allocation')
                    ->where('bh_id', $budgetHeadRecord->id)
                    ->where('status', 1)
                    ->sum('amount');
                
                Log::info('Total PD-wise allocation fetched', [
                    'budget_head' => $budgetHead,
                    'budget_head_id' => $budgetHeadRecord->id,
                    'allocated_amount' => $allocatedAmount
                ]);
            }

            // Calculate total releases from ALL THREE tables (TSA, LOA, Admin Exp)
            $totalReleases = 0;
            $excludeType = $request->input('exclude_type');
            $excludeId = $request->input('exclude_id') ? (int) $request->input('exclude_id') : null;
            
            if ($programDivisionId) {
                $totalReleases = $this->sumReleasesForBudgetAndPd(
                    $budgetHead,
                    (int) $programDivisionId,
                    $excludeType,
                    $excludeId
                );
                
                Log::info('PD-specific releases calculated from all tables', [
                    'budget_head' => $budgetHead,
                    'program_division_id' => $programDivisionId,
                    'total_releases' => $totalReleases,
                    'balanced_amount' => $allocatedAmount - $totalReleases,
                    'exclude_type' => $excludeType,
                    'exclude_id' => $excludeId,
                ]);
            } else {
                // Sum releases from TSA only for the budget head (backward compatibility)
                $totalReleases = AgencyReleaseTSA::where('budget_head', $budgetHead)
                    ->where('status', 1)
                    ->sum('amount');
                
                Log::info('Total agency releases calculated', [
                    'budget_head' => $budgetHead,
                    'total_releases' => $totalReleases,
                    'balanced_amount' => $allocatedAmount - $totalReleases
                ]);
            }

            return response()->json([
                'allocated_amount' => $allocatedAmount ?? 0,
                'total_releases' => $totalReleases ?? 0
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching balanced fund amount', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balanced fund amount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get balanced fund amount for a budget head (LOA)
     * When both budget_head and program_division are provided:
     *   Returns: Amount allocated for that Budget head and Program Division - 
     *            (sum of ALL amounts from TSA + LOA + Admin Exp for that Budget head and Program Division)
     */
    public function getBalancedFundAmountLOA(Request $request): JsonResponse
    {
        try {
            $budgetHead = $request->input('budget_head');
            $programDivisionId = $request->input('program_division_id');

            if (!$budgetHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget head is required'
                ], 400);
            }

            $budgetHeadRecord = BudgetHead::where('budget', $budgetHead)->first();

            if (!$budgetHeadRecord) {
                return response()->json([
                    'allocated_amount' => 0,
                    'total_releases' => 0
                ]);
            }

            // Calculate allocated amount based on whether program division is selected
            $allocatedAmount = 0;
            if ($programDivisionId) {
                // Get allocation for specific budget head + program division
                $allocatedAmount = DB::table('pdwise_aap_allocation')
                    ->where('bh_id', $budgetHeadRecord->id)
                    ->where('pd_id', $programDivisionId)
                    ->where('status', 1)
                    ->sum('amount');
            } else {
                // Get the sum of ALL Program Divisions' allocations for this budget head
                $allocatedAmount = DB::table('pdwise_aap_allocation')
                    ->where('bh_id', $budgetHeadRecord->id)
                    ->where('status', 1)
                    ->sum('amount');
            }

            // Calculate total releases from ALL THREE tables (TSA, LOA, Admin Exp)
            $totalReleases = 0;
            $excludeType = $request->input('exclude_type');
            $excludeId = $request->input('exclude_id') ? (int) $request->input('exclude_id') : null;
            
            if ($programDivisionId) {
                $totalReleases = $this->sumReleasesForBudgetAndPd(
                    $budgetHead,
                    (int) $programDivisionId,
                    $excludeType,
                    $excludeId
                );
            } else {
                // Sum releases from LOA only for the budget head (backward compatibility)
                $totalReleases = AgencyReleaseLOA::where('budget_head', $budgetHead)
                    ->where('status', 1)
                    ->sum('amount');
            }

            return response()->json([
                'allocated_amount' => $allocatedAmount ?? 0,
                'total_releases' => $totalReleases ?? 0
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching balanced fund amount for LOA', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balanced fund amount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get balanced fund amount for a budget head (Administrative Expenditure)
     * When both budget_head and program_division are provided:
     *   Returns: Amount allocated for that Budget head and Program Division - 
     *            (sum of ALL amounts from TSA + LOA + Admin Exp for that Budget head and Program Division)
     */
    public function getBalancedFundAmountAdminExp(Request $request): JsonResponse
    {
        try {
            $budgetHead = $request->input('budget_head');
            $programDivisionId = $request->input('program_division_id');

            if (!$budgetHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget head is required'
                ], 400);
            }

            $budgetHeadRecord = BudgetHead::where('budget', $budgetHead)->first();

            if (!$budgetHeadRecord) {
                return response()->json([
                    'allocated_amount' => 0,
                    'total_releases' => 0
                ]);
            }

            // Calculate allocated amount based on whether program division is selected
            $allocatedAmount = 0;
            if ($programDivisionId) {
                // Get allocation for specific budget head + program division
                $allocatedAmount = DB::table('pdwise_aap_allocation')
                    ->where('bh_id', $budgetHeadRecord->id)
                    ->where('pd_id', $programDivisionId)
                    ->where('status', 1)
                    ->sum('amount');
            } else {
                // Get the sum of ALL Program Divisions' allocations for this budget head
                $allocatedAmount = DB::table('pdwise_aap_allocation')
                    ->where('bh_id', $budgetHeadRecord->id)
                    ->where('status', 1)
                    ->sum('amount');
            }

            // Calculate total releases from ALL THREE tables (TSA, LOA, Admin Exp)
            $totalReleases = 0;
            $excludeType = $request->input('exclude_type');
            $excludeId = $request->input('exclude_id') ? (int) $request->input('exclude_id') : null;
            
            if ($programDivisionId) {
                $totalReleases = $this->sumReleasesForBudgetAndPd(
                    $budgetHead,
                    (int) $programDivisionId,
                    $excludeType,
                    $excludeId
                );
            } else {
                // Sum releases from Admin Exp only for the budget head (backward compatibility)
                $totalReleases = AgencyReleaseAdministrativeExpenditure::where('budget_head', $budgetHead)
                    ->where('status', 1)
                    ->sum('amount');
            }

            return response()->json([
                'allocated_amount' => $allocatedAmount ?? 0,
                'total_releases' => $totalReleases ?? 0
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching balanced fund amount for Admin Exp', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balanced fund amount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get TSA history list
     */
    public function tsaHistory(): JsonResponse
    {
        return $this->getHistoryByType('tsa');
    }

    /**
     * Get LOA history list
     */
    public function loaHistory(): JsonResponse
    {
        return $this->getHistoryByType('loa');
    }

    /**
     * Get Administrative Expenditure history list
     */
    public function administrativeExpenditureHistory(): JsonResponse
    {
        return $this->getHistoryByType('administrative-expenditure');
    }

    /**
     * Common history fetcher by type
     */
    private function getHistoryByType(string $type): JsonResponse
    {
        try {
            $history = AgencyReleaseHistory::with('programDivision')
                ->where('release_type', $type)
                ->orderBy('history_timestamp', 'desc')
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->history_id,
                        'sanction_number' => $record->sanction_number,
                        'date' => $record->date,
                        'budget_head' => $record->budget_head,
                        'purpose_of_grant' => $record->purpose_of_grant,
                        'program_division' => $record->programDivision->division_name ?? '',
                        'program_division_id' => $record->program_division_id,
                        'amount' => $record->amount,
                        'expenditure' => $record->expenditure,
                        'central_implementing_agency' => $record->central_implementing_agency,
                        'ut' => $record->ut,
                        'agency_vendor' => $record->agency_vendor,
                        'status' => $record->status,
                        'action_type' => $record->action_type,
                        'changed_by' => $record->changed_by,
                        'history_timestamp' => $record->history_timestamp,
                    ];
                });

            return response()->json($history);
        } catch (\Exception $e) {
            Log::error('Error fetching Agency Release history', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Agency Release history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save history record for agency release changes
     */
    private function saveAgencyReleaseHistory(string $type, $record, string $actionType, ?string $description = null): void
    {
        try {
            $changedBy = Auth::check() ? Auth::user()->name : 'System';

            AgencyReleaseHistory::create([
                'release_type' => $type,
                'release_id' => $record->id,
                'sanction_number' => $record->sanction_number ?? null,
                'date' => $record->date ?? null,
                'budget_head' => $record->budget_head ?? null,
                'purpose_of_grant' => $record->purpose_of_grant ?? null,
                'program_division_id' => $record->program_division_id ?? null,
                'amount' => $record->amount ?? null,
                'expenditure' => $type === 'tsa' ? ($record->expenditure ?? null) : null,
                'central_implementing_agency' => $record->central_implementing_agency ?? null,
                'ut' => $record->ut ?? null,
                'agency_vendor' => $record->agency_vendor ?? null,
                'status' => $record->status ?? 1,
                'action_type' => $actionType,
                'changed_by' => $changedBy,
                'change_description' => $description,
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving Agency Release history', [
                'type' => $type,
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


