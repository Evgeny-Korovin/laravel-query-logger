<?php

use Godmode\QueryLogger\Http\Controllers\QueryLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('sql-queries', [QueryLogController::class, 'index'])->name('query-logs.index');
    Route::delete('sql-queries', [QueryLogController::class, 'destroy'])->name('query-logs.destroy');
    Route::get('sql-queries/{queryLog}/explain', [QueryLogController::class, 'explain'])->name('query-logs.explain');
    Route::post('sql-queries/{queryLog}/ai-advice', [QueryLogController::class, 'aiAdvice'])->name('query-logs.ai-advice');
});
