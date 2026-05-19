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

    use App\Http\Controllers\Auth\AuthController;
    use App\Http\Controllers\RapidConsultingController;
    use App\Http\Controllers\N8nWebhookController;
    use App\Http\Controllers\UserDashboardController;

    Route::get('/', function () {
        return view('index');
    });

    Route::view('/terms', 'terms')->name('terms');
    Route::view('/privacy', 'privacy-policy')->name('privacy');
    Route::view('/privacy-policy', 'privacy-policy');
    Route::view('/cookies', 'cookie-policy')->name('cookies');
    Route::view('/cookie-policy', 'cookie-policy');

    Route::get('/thank-you', function () {
        return view('thank-you');
    })->name('thank-you');

    // Public — guest only
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
        Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    });

    // Rapid Consulting Home (Accessible by guests and logged-in users)
    Route::get('/rapid-consulting', [RapidConsultingController::class, 'index'])->name('rapid-consulting.index');
    
    // Direct access to specific frameworks
    Route::get('/pir', function() { return redirect()->route('rapid-consulting.index', ['type' => 'pir']); });
    Route::get('/sir', function() { return redirect()->route('rapid-consulting.index', ['type' => 'sir']); });

    // Public — assessment flow (mixed: guest starts, auth continues)
    Route::post('/rapid-consulting/start', [RapidConsultingController::class, 'start'])->name('rapid-consulting.start');
    // Public — assessment flow
    Route::get('/rapid-consulting/select-type', [RapidConsultingController::class, 'selectType'])->name('rapid-consulting.select-type');
    Route::post('/rapid-consulting/select-type', [RapidConsultingController::class, 'storeType'])->name('rapid-consulting.store-type');
    Route::get('/rapid-consulting/context', [RapidConsultingController::class, 'context'])->name('rapid-consulting.context');
    Route::post('/rapid-consulting/context', [RapidConsultingController::class, 'storeContext'])->name('rapid-consulting.store-context');
    Route::get('/rapid-consulting/assessment', [RapidConsultingController::class, 'assessment'])->name('rapid-consulting.assessment');
    Route::post('/rapid-consulting/assessment/submit', [RapidConsultingController::class, 'submit'])
        ->middleware('throttle:public-diagnostics')
        ->name('rapid-consulting.submit');
    Route::get('/assessment', [RapidConsultingController::class, 'assessment'])->name('assessment');
    Route::get('/rapid-consulting/personal-form', [RapidConsultingController::class, 'personalForm'])->name('rapid-consulting.personal-form');
    Route::post('/rapid-consulting/personal-form', [RapidConsultingController::class, 'processPersonalForm'])
        ->middleware('throttle:public-diagnostics')
        ->name('rapid-consulting.process-personal-form');
    Route::get('/rapid-consulting/results', [RapidConsultingController::class, 'results'])->name('rapid-consulting.results');
    Route::get('/rapid-consulting/report-status', [RapidConsultingController::class, 'reportStatus'])
        ->middleware('throttle:public-diagnostics')
        ->name('rapid-consulting.report-status');
    // Service Inquiries
    Route::post('/services/dev/submit', [\App\Http\Controllers\ServiceLeadController::class, 'submitDev'])->name('services.dev.submit');
    Route::post('/services/consulting/submit', [\App\Http\Controllers\ServiceLeadController::class, 'submitConsulting'])->name('services.consulting.submit');
    Route::post('/services/project/submit', [\App\Http\Controllers\ServiceLeadController::class, 'submitProject'])->name('services.project.submit');
    Route::post('/services/ai/submit', [\App\Http\Controllers\ServiceLeadController::class, 'submitAI'])->name('services.ai.submit');
    Route::post('/contact/submit', [\App\Http\Controllers\ServiceLeadController::class, 'submitContact'])->name('contact.submit');

    Route::get('/rapid-consulting/dashboard', [RapidConsultingController::class, 'dashboard'])->name('rapid-consulting.dashboard');
    Route::get('/rapid-consulting/download', [RapidConsultingController::class, 'download'])->name('rapid-consulting.download');

    // Webhook from n8n — no auth, no CSRF
    Route::post('/webhooks/n8n/receive', [N8nWebhookController::class, 'receive'])
        ->name('rapid-consulting.webhook.receive');

    // Auth
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // User portal
    Route::middleware(['auth', 'user-only'])->prefix('user')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
        Route::get('/results/{lead}', [UserDashboardController::class, 'results'])->name('user.results');
    });

    // Admin portal
    Route::middleware(['auth', 'admin-only'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings');
        Route::post('settings/password', [\App\Http\Controllers\Admin\SettingsController::class, 'updatePassword'])->name('settings.password');
        
        Route::get('assessments/create', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'create'])->name('assessments.create');
        Route::post('assessments', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'store'])->name('assessments.store');
        Route::get('assessments/phi/{assessment}/score', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'scorePhi'])->name('assessments.score.phi');
        Route::get('assessments/itsm/{assessment}/score', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'scoreItsm'])->name('assessments.score.itsm');
        Route::put('assessments/{assessment}/auto-save', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'autoSave'])->name('assessments.autosave');
        Route::post('assessments/{assessment}/generate-report', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'generateReport'])->name('assessments.generateReport');
        Route::post('assessments/{assessment}/export-pdf', [\App\Http\Controllers\Admin\AssessmentScoringController::class, 'exportPdf'])->name('assessments.exportPdf');
        
        Route::resource('clients', \App\Http\Controllers\Admin\ClientController::class);
        Route::resource('assessments', \App\Http\Controllers\Admin\AssessmentController::class)->except(['create', 'store']);
        Route::put('/assessments/{assessment}/status', [\App\Http\Controllers\Admin\AssessmentController::class, 'updateStatus'])->name('assessments.updateStatus');
        Route::get('leads', [\App\Http\Controllers\Admin\LeadController::class, 'index'])->name('leads.index');
        Route::get('leads/export', [\App\Http\Controllers\Admin\LeadController::class, 'export'])->name('leads.export');
        Route::patch('leads/{lead}', [\App\Http\Controllers\Admin\LeadController::class, 'update'])->name('leads.update');
        Route::post('leads/{lead}/convert', [\App\Http\Controllers\Admin\LeadController::class, 'convert'])->name('leads.convert');
        Route::get('bookings', [AdminBookingsController::class, 'index'])->name('bookings.index');

        // Framework Management
        Route::get('frameworks', [\App\Http\Controllers\Admin\FrameworkManagerController::class, 'index'])->name('frameworks.index');
        Route::put('frameworks/pillars/{pillar}', [\App\Http\Controllers\Admin\FrameworkManagerController::class, 'updatePillar'])->name('frameworks.updatePillar');
        Route::post('frameworks/questions', [\App\Http\Controllers\Admin\FrameworkManagerController::class, 'storeQuestion'])->name('frameworks.storeQuestion');
        Route::put('frameworks/questions/{question}', [\App\Http\Controllers\Admin\FrameworkManagerController::class, 'updateQuestion'])->name('frameworks.updateQuestion');
        Route::delete('frameworks/questions/{question}', [\App\Http\Controllers\Admin\FrameworkManagerController::class, 'destroyQuestion'])->name('frameworks.destroyQuestion');
    });


    // Fallback for static views
    Route::get('/{page}', function ($page) {
        $view = str_replace('/', '.', $page);
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '.*');
