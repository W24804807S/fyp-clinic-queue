<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\StaffController;

// Patient Portal Routes
Route::get('/', [PatientController::class, 'index'])->name('patient.index');
Route::post('/token', [PatientController::class, 'generateToken'])->name('token.generate');
Route::get('/status/{id}', [PatientController::class, 'checkStatus'])->name('patient.status');

// Language Switcher Route
Route::get('/lang/{locale}', [PatientController::class, 'switchLang'])->name('lang.switch');

// Staff Portal Routes
Route::get('/staff/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');
Route::post('/staff/call-next', [StaffController::class, 'callNext'])->name('staff.callNext');
Route::post('/staff/upgrade/{id}', [StaffController::class, 'upgradeToPriority'])->name('staff.upgrade');

// Off-hours test override route
Route::post('/staff/override-toggle', [StaffController::class, 'toggleTestOverride'])->name('staff.override.toggle');

// Staff Authentication Routes
Route::get('/staff/login', [StaffController::class, 'showLogin'])->name('staff.login');
Route::post('/staff/login', [StaffController::class, 'login'])->name('staff.login.submit');
Route::post('/staff/logout', [StaffController::class, 'logout'])->name('staff.logout');