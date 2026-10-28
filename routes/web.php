<?php
use App\Http\Controllers\AttendanceClaimController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AttendanceClaimController::class, 'index'])->name('claims.index');
Route::get('/claims/check', [AttendanceClaimController::class, 'check'])->name('claims.check')->middleware('throttle:90,1');
Route::post('/claims', [AttendanceClaimController::class, 'store'])->name('claims.store');
Route::delete('/claims/{claim}', [AttendanceClaimController::class, 'destroy'])->name('claims.destroy');

// Events
Route::post('/events', [AttendanceClaimController::class, 'storeEvent'])->name('claims.events.store');
Route::get('/events/{event}/export', [AttendanceClaimController::class, 'exportEvent'])->name('claims.events.export');
Route::delete('/events/{event}', [AttendanceClaimController::class, 'destroyEvent'])->name('claims.events.destroy');
