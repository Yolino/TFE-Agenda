<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\CongeController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JustificatifAbsenceController;
use App\Http\Controllers\SsoClientController;
// use App\Http\Controllers\CronController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// PARAMETRES
Route::view('/parametres', 'parametres')->name('parametres')->middleware('auth');

// AUTH
Route::prefix('/')->controller(AuthController::class)->name('auth.')->group(function () {
    Route::get('/', 'index')->name('index')->middleware('guest');
    Route::post('/', 'login')->name('login')->middleware('guest');
    Route::get('logout', 'logout')->name('logout')->middleware('auth');
});

// PROFILE
Route::prefix('/')->controller(ProfileController::class)->name('profile.')->middleware('auth')->group(function () {
    Route::get('mon-profile', 'show')->name('show');
    Route::post('update-planning', 'updatePlanning')->name('updatePlanning');
});

// PASSWORD
Route::prefix('/')->controller(PasswordController::class)->name('password.')->middleware('guest')->group(function () {
    Route::get('mot-de-passe-oublie', 'request')->name('request');
    Route::post('mot-de-passe-oublie', 'email')->name('email');
    Route::get('reinitialiser/{token}', 'reset')->name('reset');
    Route::post('reinitialiser', 'update')->name('update');
});

// PLANNING
Route::prefix('/mon-planning')->controller(PlanningController::class)->name('mon-planning.')->middleware('auth')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/store', 'store');
    Route::get('/show', 'show');
    Route::patch('/update/{id}', 'update');
    Route::get('/destroy/{id}', 'destroy');
    Route::post('/fill-week/{year}/{month}/{weekNumber}', 'fillWeek');
});

Route::get('/planning', [AdminController::class, 'planning'])->name('planning')->middleware('auth');


// CONGES
Route::prefix('/mes-conges')->controller(CongeController::class)->name('mes-conges.')->middleware('auth')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::put('/update/{id}', 'update')->name('update');
    Route::get('/send/{id}', 'send')->name('send');
    Route::get('/destroy/{id}', 'destroy')->name('destroy');
    Route::get('/cancel/{id}', 'cancel')->name('cancel');
    Route::get('/pdf/{id}', 'generatePDF')->name('pdf');
});

// JUSTIFICATIFS D'ABSENCE
Route::prefix('/justificatif-absence')->controller(JustificatifAbsenceController::class)->name('justificatif-absence.')->middleware('auth')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/certificat/{justificatif}', 'certificate')->name('certificat');
});

Route::prefix('/admin')->name('admin.')->middleware('auth', 'is_admin')->group(function () {
    Route::get('/user', [AdminController::class, 'user'])->name('user');
    Route::get('/conges', function () {
        return view('admin.conges');
    })->name('conges');
    Route::get('/absences', function () {
        return view('admin.absences');
    })->name('absences');
});

// LOGS SYSTÈME — accès Admin OU Directeur (middleware can_view_logs).
Route::get('/admin/logs', fn () => view('admin.logs'))
    ->name('admin.logs')
    ->middleware('auth', 'can_view_logs');

// CRON
// Route::middleware('httpbasicauth')->group(function () {
//     Route::get('/tasks/send-planning', [CronController::class, 'sendPlanning'])->name('cron.sendPlanning');
// });

Route::get('/admin/test-excel-to-pdf', function () {
    return view('admin.test_excel_to_pdf');
})->name('admin.testExcelToPdf');

Route::post('/admin/excel-to-pdf', [AdminController::class, 'excelToPdf'])->name('admin.excelToPdf');

Route::get('/admin/planning/export/{week}/{year}', [AdminController::class, 'exportPlanning'])->name('planning.export')->middleware('auth', 'is_admin');
Route::get('/admin/planning/export-pdf/{week}/{year}', [AdminController::class, 'exportPlanningPdf'])->name('planning.export.pdf')->middleware('auth', 'is_admin');

// TEAMS
Route::view('/teams', 'teams')->name('teams');

Route::get('/sso/login/{provider}', [SsoClientController::class, 'redirectToProvider'])
    ->name('sso.login');

Route::get('/sso/callback', [SsoClientController::class, 'handleCallback'])
    ->name('sso.callback');

Route::post('/sso/logout', [SsoClientController::class, 'logout'])
    ->name('sso.logout')
    ->middleware('auth');