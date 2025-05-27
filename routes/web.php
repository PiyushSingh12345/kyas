<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Models\MdProgramDivision;
use App\Models\MdUserType;
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

Route::get('/user-listing', function () {
    return Inertia::render('User_management/listingUser');
})->middleware(['auth', 'verified'])->name('user-listing');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Route::resource('users', UserController::class);
Route::post('/users', [UserController::class, 'store']);

// Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users', [UserController::class, 'index'])->middleware(['auth', 'verified'])->name('users.index');


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
