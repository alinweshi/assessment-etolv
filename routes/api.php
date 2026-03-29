<?php

use Illuminate\Http\Request;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    Route::apiResource('schools',  SchoolController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('students', StudentController::class);

    Route::post('students/{id}/enroll',   [StudentController::class, 'enroll']);
    Route::post('students/{id}/subjects', [StudentController::class, 'addSubject']);
    Route::get('students-report',         [StudentController::class, 'report']);
});
