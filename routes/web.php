<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamsMeetingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CalendlyWebhookController;
use App\Http\Controllers\Admin\AdminBookingsController;

// Teams meeting (legacy)
Route::post('/arrange-teams-meeting', [TeamsMeetingController::class, 'arrangeMeeting'])->name('teams.arrange');

// Public Calendly booking page
Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');

// Calendly webhook — no auth, no CSRF (excluded in bootstrap/app.php)
Route::post('/webhooks/calendly', [CalendlyWebhookController::class, 'handle'])->name('webhooks.calendly');

Route::get('/', function () {
    return view('index');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('assessments/create', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'create'])->name('assessments.create');
    Route::post('assessments', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'store'])->name('assessments.store');
    Route::get('assessments/phi/{assessment}/score', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'scorePhi'])->name('assessments.score.phi');
    Route::get('assessments/itsm/{assessment}/score', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'scoreItsm'])->name('assessments.score.itsm');
    Route::put('assessments/{assessment}/auto-save', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'autoSave'])->name('assessments.autosave');
    Route::post('assessments/{assessment}/generate-report', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'generateReport'])->name('assessments.generateReport');
    
    Route::resource('clients', \App\Http\Controllers\Admin\ClientController::class);
    Route::resource('assessments', \App\Http\Controllers\Admin\AssessmentController::class)->except(['create', 'store']);
    Route::put('/assessments/{assessment}/status', [\App\Http\Controllers\Admin\AssessmentController::class, 'updateStatus'])->name('assessments.updateStatus');
    Route::get('leads', [\App\Http\Controllers\Admin\LeadController::class, 'index'])->name('leads.index');
    Route::post('leads/{lead}/convert', [\App\Http\Controllers\Admin\LeadController::class, 'convert'])->name('leads.convert');
    Route::get('bookings', [AdminBookingsController::class, 'index'])->name('bookings.index');
});

Route::get('/rapid-consulting', [\App\Http\Controllers\RapidConsultingController::class, 'index'])->name('rapid-consulting.index');
Route::post('/rapid-consulting/start', [\App\Http\Controllers\RapidConsultingController::class, 'start'])->name('rapid-consulting.start');
Route::get('/rapid-consulting/select-type', [\App\Http\Controllers\RapidConsultingController::class, 'selectType'])->name('rapid-consulting.select-type');
Route::post('/rapid-consulting/select-type', [\App\Http\Controllers\RapidConsultingController::class, 'storeType'])->name('rapid-consulting.store-type');
Route::get('/rapid-consulting/assessment', [\App\Http\Controllers\RapidConsultingController::class, 'assessment'])->name('rapid-consulting.assessment');
Route::post('/rapid-consulting/assessment/submit', [\App\Http\Controllers\RapidConsultingController::class, 'submit'])->name('rapid-consulting.submit');
Route::get('/rapid-consulting/results', [\App\Http\Controllers\RapidConsultingController::class, 'results'])->name('rapid-consulting.results');
Route::get('/rapid-consulting/dashboard', [\App\Http\Controllers\RapidConsultingController::class, 'dashboard'])->name('rapid-consulting.dashboard');

Route::get('/{page}', function ($page) {
    if (view()->exists($page)) {
        return view($page);
    }
    abort(404);
})->where('page', '.*');
