<?php

use Illuminate\Support\Facades\Route;
use Ultra\UltraConfigManager\Http\Controllers\UltraConfigController;

Route::resource('uconfig', UltraConfigController::class)->names('uconfig');
Route::get('/uconfig/{id}/audit', [UltraConfigController::class, 'audit'])->name('uconfig.audit');
