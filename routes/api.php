<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\OptionsController;

// API endpoints for the time entry application
// Core API endpoints
Route::get('/companies', [CompanyController::class, 'index']);
Route::get('/employees', [EmployeeController::class, 'index']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/tasks', [TaskController::class, 'index']);
Route::get('/time-entries', [TimeEntryController::class, 'index']);
Route::post('/time-entry', [TimeEntryController::class, 'store']);
// Batch endpoint for creating multiple time entries with validation per entry.
Route::post('/time-entries/batch', [TimeEntryController::class, 'storeBatch']);
// Dependent dropdown endpoints (company -> employees, projects, tasks) via OptionsController
Route::get('/options/employees', [OptionsController::class, 'employees']);
Route::get('/options/projects', [OptionsController::class, 'projects']);
Route::get('/options/tasks', [OptionsController::class, 'tasks']);
// Test route
Route::get('/test', function () { return response()->json(['ok' => true]); });
