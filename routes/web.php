<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Models\MdProgramDivision;
use App\Models\MdUserType;
use App\Http\Controllers\StateController;
use App\Http\Controllers\BudgetHeadController;
use App\Http\Controllers\BudgetPhaseController;
use App\Http\Controllers\SlsPDComponentController;
use App\Http\Controllers\FundAllocationController;
use App\Http\Controllers\MotherSanctionController;
use App\Http\Controllers\DailySanctionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
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
})->middleware(['auth', 'verified'])->name('user-create');

Route::get('/user-listing', function () {
    return Inertia::render('User_management/listingUser');
})->middleware(['auth', 'verified'])->name('user-listing');

//Budget Allocation Module

Route::get('/budget-phase', function () {
    return Inertia::render('Budget_allocation/BudgetPhase');
})->middleware(['auth', 'verified'])->name('budget-phase');

Route::get('/budget-phase-report', function () {
    return Inertia::render('Reports/BudgetPhaseReport');
})->middleware(['auth', 'verified'])->name('budget-phase-report');

Route::get('/fund-allocation', function () {
    return Inertia::render('Budget_allocation/FundAllocation');
})->middleware(['auth', 'verified'])->name('fund-allocation');

Route::get('/budget-heads', function () {
    return Inertia::render('Budget_allocation/BudgetHeads');
})->middleware(['auth', 'verified'])->name('budget-heads');

Route::get('/state-uts', function () {
    return Inertia::render('Budget_allocation/StateUTs');
})->middleware(['auth', 'verified'])->name('state-uts');

Route::get('/state-uts-pd', function () {
    return Inertia::render('Budget_allocation/StateUTsPD');
})->middleware(['auth', 'verified'])->name('state-uts-pd');

Route::get('/mother-sanction', function () {
    return Inertia::render('mother_sanction/MotherSanction');
})->middleware(['auth', 'verified'])->name('mother-sanction');

Route::get('/mother-sanction-report', function () {
    return Inertia::render('Reports/MotherSanctionReport');
})->middleware(['auth', 'verified'])->name('mother-sanction-report');

Route::get('/mother-sanction-list', function () {
    return Inertia::render('mother_sanction/MotherSanctionList');
})->middleware(['auth', 'verified'])->name('mother-sanction-list');

Route::get('/daily-sanction-list', function () {
    return Inertia::render('Daily_sanction/DailySanctionList');
})->middleware(['auth', 'verified'])->name('daily-sanction-list');

Route::get('/daily-sanction', function () {
    return Inertia::render('Daily_sanction/DailySanction');
})->middleware(['auth', 'verified'])->name('daily-sanction');


Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/state-uts', [StateController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('state-uts');
    Route::post('/states', [StateController::class, 'store'])->name('states.store');
    Route::get('/budget-heads', [BudgetHeadController::class, 'index'])->name('BudgetHead.index');
    Route::post('/budget-heads', [BudgetHeadController::class, 'store'])->name('BudgetHead.store');
    Route::delete('/budget-heads/{budgetHead}', [BudgetHeadController::class, 'destroy'])->name('BudgetHead.destroy');
    Route::put('/budget-heads/{budgetHead}', [BudgetHeadController::class, 'update'])->name('BudgetHead.update');
    Route::put('/budget-heads/{id}/toggle-status', [BudgetHeadController::class, 'toggleStatus'])->name('BudgetHead.toggleStatus');

    Route::get('/api/budget-heads', [BudgetHeadController::class, 'fetchBudgetHeads']);
    Route::get('/api/budget-phase-summary', [BudgetPhaseController::class, 'budgetPhaseSummary']);


    Route::get('/api/budget-heads', [BudgetPhaseController::class, 'fetchActiveBudgetHeads']);

    Route::post('/budget-phase', [BudgetPhaseController::class, 'store'])->name('budget-phase.store');

    Route::get('/api/budget-allocation', [BudgetPhaseController::class, 'fetchActiveBudgetAllocation']);

    Route::get('/pd-sls-list', [SlsPDComponentController::class, 'index'])->name('pd-sls.list');
    Route::post('/pd-sls-store', [SlsPDComponentController::class, 'store'])->name('pd-sls.store');
    Route::get('/api/states', [StateController::class, 'getStatesApi']);

    Route::get('/api/get-components-by-fund', [SlsPDComponentController::class, 'getComponentsByFund']);
    Route::post('/api/fund-allocation', [FundAllocationController::class, 'store'])->name('fund-allocation.store');

    Route::get('/api/sls-data/{stateId}', [MotherSanctionController::class, 'getSlsData']);

    Route::get('/api/fund-allocation/{slsId}/{stateId}', [MotherSanctionController::class, 'getFundAllocationData']);

    Route::get('/api/fund-allocation/by-budget', [MotherSanctionController::class, 'getFundAllocationByBudgetHead']);

    Route::post('/api/mother-sanction', [MotherSanctionController::class, 'addMotherSanction'])->name('addMotherSanction');

    Route::get('/api/mother-sanctions-list', [MotherSanctionController::class, 'list'])->name('motherSanctions.list');

    Route::get('/api/mother-sanctions', [DailySanctionController::class, 'getMotherSanctions']);
    Route::get('api/mother-sanction-details/{ky_ms_no}', [DailySanctionController::class, 'getMotherSanctionDetails']);
    Route::post('api/daily-sanctions', [DailySanctionController::class, 'store'])->name('addDailySanction');

    Route::get('/api/daily-sanctions-list', [DailySanctionController::class, 'list'])->name('dailySanctions.list');

    Route::get('/reports/mother-sanctions-data', [MotherSanctionController::class, 'motherSanctionData']);





});

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




require __DIR__.'/auth.php';
