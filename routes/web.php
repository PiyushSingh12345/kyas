<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\BudgetHeadController;
use App\Http\Controllers\BudgetPhaseController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
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

//Budget Allocation Module

Route::get('/budget-phase', function () {
    return Inertia::render('Budget_allocation/BudgetPhase');
})->middleware(['auth', 'verified'])->name('budget-phase');

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

    Route::get('/api/budget-heads', [BudgetPhaseController::class, 'fetchActiveBudgetHeads']);

    Route::post('/budget-phase', [BudgetPhaseController::class, 'store'])->name('budget-phase.store');

    Route::get('/api/budget-allocation', [BudgetPhaseController::class, 'fetchActiveBudgetAllocation']);




});

Route::resource('users', UserController::class);
Route::post('/users', [UserController::class, 'store']);

require __DIR__.'/auth.php';
