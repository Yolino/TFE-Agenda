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
use App\Http\Controllers\CronController;

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

Route::view('/parametres', 'parametres')->name('parametres')->middleware('auth');

Route::prefix('/')->controller(AuthController::class)->name('auth.')->group(function () {
    Route::get('/', 'index')->name('index')->middleware('guest');
    Route::post('/', 'login')->name('login')->middleware('guest');
    Route::get('logout', 'logout')->name('logout')->middleware('auth');
});

Route::prefix('/')->controller(ProfileController::class)->name('profile.')->middleware('auth')->group(function () {
    Route::get('mon-profile', 'show')->name('show');
    Route::post('update-planning', 'updatePlanning')->name('updatePlanning')->middleware('has_personal_agenda');
});

Route::prefix('/')->controller(PasswordController::class)->name('password.')->middleware('guest')->group(function () {
    Route::get('mot-de-passe-oublie', 'request')->name('request');
    Route::post('mot-de-passe-oublie', 'email')->name('email');
    Route::get('reinitialiser/{token}', 'reset')->name('reset');
    Route::post('reinitialiser', 'update')->name('update');
});

Route::prefix('/mon-planning')->controller(PlanningController::class)->name('mon-planning.')->middleware('auth')->group(function () {
    // Le directeur n'a pas de planning personnel : seule la page perso lui est interdite.
    // Les endpoints d'édition restent ouverts (autorisation via le Gate manage-planning)
    // car ils servent aussi à éditer le planning général.
    Route::get('/', 'index')->name('index')->middleware('has_personal_agenda');
    Route::post('/store', 'store');
    Route::get('/show', 'show');
    Route::patch('/update/{id}', 'update');
    Route::get('/destroy/{id}', 'destroy');
    Route::post('/fill-week/{year}/{month}/{weekNumber}', 'fillWeek');
});

Route::get('/planning', [AdminController::class, 'planning'])->name('planning')->middleware('auth');


Route::prefix('/mes-conges')->controller(CongeController::class)->name('mes-conges.')->middleware('auth', 'has_personal_agenda')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::put('/update/{id}', 'update')->name('update');
    Route::get('/send/{id}', 'send')->name('send');
    Route::get('/destroy/{id}', 'destroy')->name('destroy');
    Route::get('/cancel/{id}', 'cancel')->name('cancel');
    // Consultation du PDF d'une demande : ouverte au directeur (gestion des congés),
    // autorisation fine (propriétaire ou directeur) gérée dans le contrôleur.
    Route::get('/pdf/{id}', 'generatePDF')->name('pdf')->withoutMiddleware('has_personal_agenda');
});

Route::prefix('/justificatif-absence')->controller(JustificatifAbsenceController::class)->name('justificatif-absence.')->middleware('auth')->group(function () {
    // Le directeur n'a pas de page perso de justificatifs, mais doit pouvoir consulter
    // les certificats depuis la gestion des absences (autorisation dans le contrôleur).
    Route::get('/', 'index')->name('index')->middleware('has_personal_agenda');
    Route::get('/certificat/{justificatif}', 'certificate')->name('certificat');
});

Route::prefix('/admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/user', [AdminController::class, 'user'])->name('user')->middleware('admin_or_directeur');
    Route::get('/conges', function () {
        return view('admin.conges');
    })->name('conges')->middleware('is_directeur');
    Route::get('/absences', function () {
        return view('admin.absences');
    })->name('absences')->middleware('is_directeur');
});

Route::get('/admin/logs', fn () => view('admin.logs'))
    ->name('admin.logs')
    ->middleware('auth', 'can_view_logs');

Route::middleware('httpbasicauth')->prefix('cron')->name('cron.')->group(function () {
    Route::get('/planning-hebdo', [CronController::class, 'planningHebdo'])->name('planning');
    Route::get('/conges-attente', [CronController::class, 'congesAttente'])->name('conges');
    Route::get('/test', [CronController::class, 'test'])->name('test');
});

Route::get('/admin/test-excel-to-pdf', function () {
    return view('admin.test_excel_to_pdf');
})->name('admin.testExcelToPdf');

Route::post('/admin/excel-to-pdf', [AdminController::class, 'excelToPdf'])->name('admin.excelToPdf');

Route::get('/admin/planning/export/{week}/{year}', [AdminController::class, 'exportPlanning'])->name('planning.export')->middleware('auth', 'admin_or_directeur');
Route::get('/admin/planning/export-pdf/{week}/{year}', [AdminController::class, 'exportPlanningPdf'])->name('planning.export.pdf')->middleware('auth', 'admin_or_directeur');

Route::view('/teams', 'teams')->name('teams');

Route::get('/sso/login/{provider}', [SsoClientController::class, 'redirectToProvider'])
    ->name('sso.login');

Route::get('/sso/callback', [SsoClientController::class, 'handleCallback'])
    ->name('sso.callback');

Route::post('/sso/logout', [SsoClientController::class, 'logout'])
    ->name('sso.logout')
    ->middleware('auth');