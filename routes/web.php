<?php
use App\Http\Controllers\AttendanceClaimController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AttendanceClaimController::class, 'index'])->name('claims.index');
Route::get('/claims/check', [AttendanceClaimController::class, 'check'])->name('claims.check')->middleware('throttle:90,1');
Route::post('/claims', [AttendanceClaimController::class, 'store'])->name('claims.store');
Route::delete('/claims/{claim}', [AttendanceClaimController::class, 'destroy'])->name('claims.destroy');
Route::delete('/claims', [AttendanceClaimController::class, 'destroyAll'])->name('claims.destroyAll');
