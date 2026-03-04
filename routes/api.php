<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\TranslationSearchController;
use App\Http\Controllers\Api\TranslationExportController;
use App\Http\Controllers\Api\TagController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/validate', [AuthController::class, 'validate'])->middleware('auth:sanctum');

// Translation routes
Route::get('/translations', [TranslationController::class, 'index']);
Route::get('/translations/search', [TranslationSearchController::class, 'search']);
Route::get('/translations/export', [TranslationExportController::class, 'export']);
Route::post('/translations', [TranslationController::class, 'store'])->middleware('auth:sanctum');
Route::get('/translations/{translation}', [TranslationController::class, 'show']);
Route::put('/translations/{translation}', [TranslationController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/translations/{translation}', [TranslationController::class, 'destroy'])->middleware('auth:sanctum');

// Tag routes
Route::get('/tags', [TagController::class, 'index']);
