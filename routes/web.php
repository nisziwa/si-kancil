<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // FPA / Permintaan Routes
    Route::resource('requests', App\Http\Controllers\RequestController::class);

    // Checklist SPJ Routes
    Route::get('/checklists/{id}/edit', [App\Http\Controllers\SpjChecklistController::class, 'edit'])->name('checklists.edit');
    Route::put('/checklists/{id}', [App\Http\Controllers\SpjChecklistController::class, 'update'])->name('checklists.update');
    Route::patch('/checklists/{id}/status', [App\Http\Controllers\ChecklistKanbanController::class, 'updateStatus'])->name('checklists.status');
    
    // Status SPJ Routes
    Route::post('/requests/{id}/status', [App\Http\Controllers\RequestStatusController::class, 'update'])->name('requests.status.update');
    Route::patch('/requests/{id}/status-ajax', [App\Http\Controllers\RequestStatusController::class, 'updateAjax'])->name('requests.status.ajax');
});

require __DIR__.'/auth.php';
