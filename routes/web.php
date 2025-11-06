<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Models\MdProgramDivision;
use App\Models\MdUserType;
use App\Http\Controllers\StateController;
use App\Http\Controllers\BudgetHeadController;
use App\Http\Controllers\BudgetPhaseController;
use App\Http\Controllers\BudgetPhaseHistoryController;
use App\Http\Controllers\SlsPDComponentController;
use App\Http\Controllers\FundAllocationController;
use App\Http\Controllers\MotherSanctionController;
use App\Http\Controllers\MotherSanctionListController;
use App\Http\Controllers\DailySanctionController;
use App\Http\Controllers\ReAppropritionController;
use App\Http\Controllers\AnnualActionPlanController;
use App\Http\Controllers\StatewiseAapAllocationHistoryController;
use App\Http\Controllers\PdWiseBudgetAllocationAAPCentralHistoryController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;


Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('user-listing');
    }
    // return Inertia::render('Welcome', [
    return Inertia::render('Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/user-create', function () {
    return Inertia::render('User_management/createUser');
})->middleware(['auth', 'verified', 'role:1'])->name('user-create');

Route::get('/user-listing', function () {
    return Inertia::render('User_management/listingUser');
})->middleware(['auth', 'verified', 'role:1'])->name('user-listing');

//Budget Allocation Module

Route::get('/budget-phase', function () {
    return Inertia::render('Budget_allocation/BudgetPhase');
})->middleware(['auth', 'verified', 'role:2,3'])->name('budget-phase');

Route::get('/budget-phase-report', function () {
    return Inertia::render('Reports/BudgetPhaseReport');
})->middleware(['auth', 'verified', 'role:1,2,4'])->name('budget-phase-report');

Route::get('/budget-phase-history', [BudgetPhaseHistoryController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:1,2,3,4'])->name('budget-phase-history');

Route::get('/statewise-aap-allocation-history', [StatewiseAapAllocationHistoryController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:1,2,3,4'])->name('statewise-aap-allocation-history');

Route::get('/pdwise-budget-allocation-aap-central-history', [PdWiseBudgetAllocationAAPCentralHistoryController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:1,2,3,4'])->name('pdwise-budget-allocation-aap-central-history');

Route::get('/fund-allocation', function () {
    return Inertia::render('Budget_allocation/FundAllocation');
})->middleware(['auth', 'verified', 'role:2'])->name('fund-allocation');

Route::get('/budget-heads', [BudgetHeadController::class, 'index'])->middleware(['auth', 'verified', 'role:2,3'])->name('budget-heads');


Route::get('/state-uts', function () {
    return Inertia::render('Budget_allocation/StateUTs');
})->middleware(['auth', 'verified', 'role:2,3'])->name('state-uts');

Route::get('/state-uts-pd', function () {
    return Inertia::render('Budget_allocation/StateUTsPD');
})->middleware(['auth', 'verified', 'role:2,3'])->name('state-uts-pd');

Route::get('/mother-sanction', function () {
    return Inertia::render('mother_sanction/MotherSanction');
})->middleware(['auth', 'verified', 'role:2'])->name('mother-sanction');

Route::get('/mother-sanction-report', function () {
    return Inertia::render('Reports/MotherSanctionReport');
})->middleware(['auth', 'verified', 'role:1,2,4'])->name('mother-sanction-report');

Route::get('/mother-sanction-list', function () {
    return Inertia::render('Reports/MotherSanctionList');
})->middleware(['auth', 'verified', 'role:1,2,4'])->name('mother-sanction-list');


Route::get('/daily-sanction-list', function () {
    return Inertia::render('Daily_sanction/DailySanctionList');
})->middleware(['auth', 'verified', 'role:2'])->name('daily-sanction-list');

Route::get('/daily-sanction', function () {
    return Inertia::render('Daily_sanction/DailySanction');
})->middleware(['auth', 'verified', 'role:2'])->name('daily-sanction');

Route::get('/re-appropriation-of-funds', function () {
    return Inertia::render('Reappropriation/ReAppropriationOfFunds');
})->middleware(['auth', 'verified', 'role:2'])->name('re-appropriation-of-funds');

Route::get('/re-appropriation-mis-report', function () {
    return Inertia::render('Reports/ReAppropriationMisReport');
})->middleware(['auth', 'verified', 'role:1,2,4'])->name('re-appropriation-mis-report');

Route::get('/fund-allocation-report', function () {
    return Inertia::render('Reports/FundAllocationReport');
})->middleware(['auth', 'verified', 'role:1,2,4'])->name('fund-allocation-report');

Route::get('/rog-report', function () {
    return Inertia::render('Reports/RogReport');
})->middleware(['auth', 'verified', 'role:1,2,4'])->name('rog-report');

Route::get('/daily-sanction-time-series-report', function () {
    return Inertia::render('Reports/DailySanctionTimeSeriesReport');
})->middleware(['auth', 'verified', 'role:1,2,4'])->name('daily-sanction-time-series-report');

Route::get('/mother-sanction-time-series-report', function () {
    return Inertia::render('Reports/MotherSanctionTimeSeriesReport');
})->middleware(['auth', 'verified', 'role:1,2,4'])->name('mother-sanction-time-series-report');

Route::get('/mother-sanction-list-module', function () {
    return Inertia::render('mother_sanction/MotherSanctionList');
})->middleware(['auth', 'verified', 'role:2'])->name('mother-sanction-list-module');

Route::get('/pd-wise-budget-allocation', function () {
    return Inertia::render('Annual_action_plan/AapCentral');
})->middleware(['auth', 'verified', 'role:2'])->name('pd-wise-budget-allocation');

Route::get('/state-release-data', function () {
    return Inertia::render('Annual_action_plan/AapState');
})->middleware(['auth', 'verified', 'role:2'])->name('state-release-data');

Route::get('/statewise-aap-allocation', function () {
    return Inertia::render('Annual_action_plan/StatewiseAapAllocation');
})->middleware(['auth', 'verified', 'role:2'])->name('statewise-aap-allocation');

Route::get('/role-based-access-demo', function () {
    return Inertia::render('RoleBasedAccessDemo');
})->middleware(['auth', 'verified'])->name('role-based-access-demo');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/state-uts', [StateController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:2,3'])
    ->name('state-uts');
    Route::post('/states', [StateController::class, 'store'])->middleware(['role:2,3'])->name('states.store');


    Route::post('/budget-heads', [BudgetHeadController::class, 'store'])->middleware(['role:2,3'])->name('BudgetHead.store');
    Route::post('/budget-heads/upload', [BudgetHeadController::class, 'upload'])->middleware(['role:2,3'])->name('BudgetHead.upload');
    Route::post('/budget-heads/import', [BudgetHeadController::class, 'import'])->middleware(['role:2,3'])->name('BudgetHead.import');
    Route::delete('/budget-heads/{budgetHead}', [BudgetHeadController::class, 'destroy'])->middleware(['role:2,3'])->name('BudgetHead.destroy');
    Route::put('/budget-heads/{budgetHead}', [BudgetHeadController::class, 'update'])->middleware(['role:2,3'])->name('BudgetHead.update');
    Route::put('/budget-heads/{id}/toggle-status', [BudgetHeadController::class, 'toggleStatus'])->middleware(['role:2,3'])->name('BudgetHead.toggleStatus');

    // Route::get('/api/budget-heads', [BudgetHeadController::class, 'fetchBudgetHeads']);
    Route::get('/api/budget-phase-summary', [BudgetPhaseController::class, 'budgetPhaseSummary']);


   Route::get('/api/budget-heads', [BudgetPhaseController::class, 'fetchActiveBudgetHeads']);

    Route::post('/budget-phase', [BudgetPhaseController::class, 'store'])->name('budget-phase.store');

    Route::get('/api/budget-allocation', [BudgetPhaseController::class, 'fetchActiveBudgetAllocation']);

    // Budget Phase History API routes
    Route::get('/api/budget-phase-history', [BudgetPhaseHistoryController::class, 'getHistoryData']);
    Route::get('/api/budget-heads-with-history', [BudgetPhaseHistoryController::class, 'getBudgetHeadsWithHistory']);
    Route::get('/api/statewise-aap-allocation-history', [StatewiseAapAllocationHistoryController::class, 'getHistoryData']);
    Route::get('/api/states-with-history', [StatewiseAapAllocationHistoryController::class, 'getStatesWithHistory']);
    Route::get('/api/program-divisions-with-history', [StatewiseAapAllocationHistoryController::class, 'getProgramDivisionsWithHistory']);
    Route::get('/api/pdwise-aap-allocation-history', [PdWiseBudgetAllocationAAPCentralHistoryController::class, 'getHistoryData']);
    Route::get('/api/budget-heads-with-history-pdwise', [PdWiseBudgetAllocationAAPCentralHistoryController::class, 'getBudgetHeadsWithHistory']);
    Route::get('/api/program-divisions-with-history-pdwise', [PdWiseBudgetAllocationAAPCentralHistoryController::class, 'getProgramDivisionsWithHistory']);
    Route::get('/api/users', function() {
        return response()->json([
            'success' => true,
            'data' => \App\Models\User::select('id', 'name', 'email')->get()
        ]);
    });

    Route::get('/pd-sls-list', [SlsPDComponentController::class, 'index'])->name('pd-sls.list');
    Route::get('/pd-components-list', [SlsPDComponentController::class, 'getPDComponents'])->name('pd-components.list');
Route::get('/pd-components-dropdown', [SlsPDComponentController::class, 'getPDComponentsForDropdown'])->name('pd-components.dropdown');
    Route::post('/pd-sls-store', [SlsPDComponentController::class, 'store'])->name('pd-sls.store');
    Route::delete('/pd-sls/{id}', [SlsPDComponentController::class, 'destroy'])->name('pd-sls.destroy');
    Route::post('/pd-sls/upload-excel', [SlsPDComponentController::class, 'uploadExcel'])->name('pd-sls.upload-excel');
    Route::post('/pd-sls/save-sls-data', [SlsPDComponentController::class, 'saveSLSData'])->name('pd-sls.save-sls-data');
    Route::post('/pd-sls/update-mappings', [SlsPDComponentController::class, 'updatePDSLSMappings'])->name('pd-sls.update-mappings');
    Route::get('/api/states', [StateController::class, 'getStatesApi']);
    
    // Statewise AAP Allocation API routes
    Route::post('/api/statewise-aap-allocation', [AnnualActionPlanController::class, 'storeStatewiseAllocation']);
    Route::get('/api/statewise-aap-allocation', [AnnualActionPlanController::class, 'getStatewiseAllocation']);
    Route::get('/api/aap-states', [AnnualActionPlanController::class, 'getStates']);
    Route::get('/api/aap-program-divisions', [AnnualActionPlanController::class, 'getProgramDivisions']);
    
    // PD-wise AAP Allocation API routes
    Route::post('/api/pdwise-aap-allocation', [AnnualActionPlanController::class, 'storePdwiseAllocation']);
    Route::get('/api/pdwise-aap-allocation', [AnnualActionPlanController::class, 'getPdwiseAllocation']);
    Route::get('/api/aap-budget-heads', [AnnualActionPlanController::class, 'getBudgetHeads']);
    
    // State Release Data API routes
    Route::get('/api/aap-sls-components', [AnnualActionPlanController::class, 'getSLSComponentsByState']);
    Route::post('/api/aap-state-release-data', [AnnualActionPlanController::class, 'storeStateReleaseData']);
    Route::get('/api/aap-budget-heads-by-state', [AnnualActionPlanController::class, 'getBudgetHeadsByState']);
    Route::get('/api/aap-state-release-generic', [AnnualActionPlanController::class, 'getStateReleaseGeneric']);
    Route::get('/api/aap-state-release-data', [AnnualActionPlanController::class, 'getStateReleaseData']);
    
    // Test route for debugging
    Route::get('/api/test-states', function() {
        try {
            $states = \Illuminate\Support\Facades\DB::table('states')->select('id', 'name')->get();
            return response()->json(['success' => true, 'data' => $states]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    Route::get('/api/get-components-by-fund', [SlsPDComponentController::class, 'getComponentsByFund']);
Route::post('/api/fund-allocation', [FundAllocationController::class, 'store'])->name('fund-allocation.store');


// Test route for PDF processing
Route::get('/test-pdf', function() {
    try {
        $parser = new \Smalot\PdfParser\Parser();
        return response()->json(['status' => 'PDF Parser is working']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'Error: ' . $e->getMessage()]);
    }
});

// Test route for Excel parsing
Route::get('/test-excel-parse', function() {
    try {
        $sampleData = [
            ['SLS Code', 'SLS Name', 'State Name', 'SG Account', 'Sharing Pattern(Centre)', 'Sharing Pattern(State)'],
            ['AP17', 'National Food Security', 'Andhra Pradesh', '01604901079', '60', '40'],
            ['AP24', 'Sub Mission on Agriculture', 'Andhra Pradesh', '01604901081', '90', '10'],
            ['AP56', 'National Mission on', 'Andhra Pradesh', '01604901080', '60', '40']
        ];
        
        $controller = new \App\Http\Controllers\SlsPDComponentController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('uploadExcel');
        $method->setAccessible(true);
        
        // Create a mock request
        $request = new \Illuminate\Http\Request();
        $request->merge(['file' => 'test.xlsx']);
        
        $result = $method->invoke($controller, $request);
        
        return response()->json([
            'status' => 'Excel parsing test successful',
            'sample_data' => $sampleData
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'Error: ' . $e->getMessage()]);
    }
});

// Test route for Excel file structure debugging
Route::post('/debug-excel', function(Request $request) {
    try {
        $file = $request->file('file');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        return response()->json([
            'success' => true,
            'filename' => $file->getClientOriginalName(),
            'total_rows' => count($rows),
            'first_10_rows' => array_slice($rows, 0, 10),
            'row_count_by_type' => [
                'empty_rows' => count(array_filter($rows, function($row) { return empty(array_filter($row)); })),
                'non_empty_rows' => count(array_filter($rows, function($row) { return !empty(array_filter($row)); }))
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});





    Route::get('/api/sls-data/{stateId}', [MotherSanctionController::class, 'getSlsData']);

    Route::get('/api/fund-allocation/{slsId}/{stateId}', [MotherSanctionController::class, 'getFundAllocationData']);

    Route::get('/api/fund-allocation/by-budget', [MotherSanctionController::class, 'getFundAllocationByBudgetHead']);

    Route::post('/api/mother-sanction', [MotherSanctionController::class, 'addMotherSanction'])->name('addMotherSanction');

    Route::get('/api/mother-sanctions-list', [MotherSanctionController::class, 'list'])->name('motherSanctions.list');

    Route::get('/api/mother-sanctions-list-report', [MotherSanctionController::class, 'listReport'])->name('motherSanctions.listReport');

    Route::post('/api/mother-sanction/update-status', [MotherSanctionController::class, 'updateStatus'])->name('motherSanction.updateStatus');

    Route::get('/api/mother-sanction-details/{ky_ms_no}', [MotherSanctionController::class, 'getMotherSanctionDetails'])->name('motherSanction.details');

    Route::get('/api/mother-sanctions', [DailySanctionController::class, 'getMotherSanctions']);
    Route::get('api/mother-sanction-details/{ky_ms_no}', [DailySanctionController::class, 'getMotherSanctionDetails']);
    Route::post('api/daily-sanctions', [DailySanctionController::class, 'store'])->name('addDailySanction');

    Route::get('/api/daily-sanctions-list', [DailySanctionController::class, 'list'])->name('dailySanctions.list');
    Route::get('/api/daily-sanction-time-series', [DailySanctionController::class, 'timeSeriesReport'])->name('dailySanction.timeSeriesReport');

    // PDF processing routes for Daily Sanction
    Route::post('/api/daily-sanction/process-pdf', [App\Http\Controllers\DailySanctionPdfController::class, 'processPdf'])->name('dailySanction.processPdf');
    Route::get('/api/daily-sanction/mother-sanctions', [App\Http\Controllers\DailySanctionPdfController::class, 'getMotherSanctionsByState'])->name('dailySanction.motherSanctions');

    Route::get('/reports/mother-sanctions-data', [MotherSanctionController::class, 'motherSanctionData']);
    Route::get('/api/mother-sanction-time-series', [MotherSanctionController::class, 'timeSeriesReport'])->name('motherSanction.timeSeriesReport');





});

Route::get('api/reappropriations', [ReAppropritionController::class, 'index']);
Route::post('api/reappropriations', [ReAppropritionController::class, 'store']);
Route::get('api/budget-phase/amount', [ReAppropritionController::class, 'getBudgetAmountByHead']);

Route::resource('users', UserController::class);
Route::post('/users', [UserController::class, 'store']);

// Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users', [UserController::class, 'index'])->middleware(['auth', 'verified'])->name('users.index');

Route::get('/api/user-counts', [UserController::class, 'userCounts']);

// Route::get('/md-program-divisions', function () {
//     return \App\Models\MdProgramDivision::select('id', 'name')->get();
// });

Route::get('/md-program-divisions', function () {
    return MdProgramDivision::select('division_id', 'division_name')->get();
});

Route::get('/md-user-types', function () {
    return MdUserType::select('md_user_type_id', 'user_type_name')->get();
});



Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);




Route::post('/sls-upload', [App\Http\Controllers\SLSController::class, 'upload'])->name('sls.upload');

require __DIR__.'/auth.php';
