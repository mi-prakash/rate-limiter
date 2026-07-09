<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
* Demo API Routes
*
* These endpoints simulate a small healthcare API and serve as the
* integration example required for the rate-limiting challenge.
*
* The custom middleware is applied at the route-group level so every
* endpoint is protected without modifying individual route handlers.
*/

Route::middleware('custom.rate.limit')->group(function () {
    /**
     * Retrieve a list of patients.
     */
    Route::get('/patients', function () {
        return response()->json([
            'message' => 'Patient list retrieved successfully',
        ]);
    });

    /**
     * Create a new patient.
     */
    Route::post('/patients', function () {
        return response()->json([
            'message' => 'Patient created successfully',
        ]);
    });

    /**
     * Update an existing patient.
     */
    Route::put('/patients/{id}', function (string $id) {
        return response()->json([
            'message' => "Patient {$id} updated successfully",
        ]);
    });

    /**
     * Delete an existing patient.
     */
    Route::delete('/patients/{id}', function (string $id) {
        return response()->json([
            'message' => "Patient {$id} deleted successfully",
        ]);
    });
});