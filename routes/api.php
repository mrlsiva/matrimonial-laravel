<?php

use App\Http\Controllers\Api\MasterDataController;
use Illuminate\Support\Facades\Route;

/*
| Public, read-only REST endpoints used by dependent dropdowns (and usable by a mobile app).
*/
Route::middleware('throttle:60,1')->name('api.')->group(function () {
    Route::get('/religions', [MasterDataController::class, 'religions'])->name('religions');
    Route::get('/religions/{religion}/castes', [MasterDataController::class, 'castes'])->name('castes');
    Route::get('/states', [MasterDataController::class, 'states'])->name('states');
    Route::get('/states/{state}/cities', [MasterDataController::class, 'cities'])->name('cities');
    Route::get('/plans', [MasterDataController::class, 'plans'])->name('plans');
});
