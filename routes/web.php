<?php

use App\Http\Controllers\BidscapeController;
use App\Http\Controllers\JobPacketPdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicEstimateReviewController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome');
})->name('home');

Route::get('/estimate-review/{token}', [PublicEstimateReviewController::class, 'show'])->name('estimate-review.show');
Route::post('/estimate-review/{token}/approve', [PublicEstimateReviewController::class, 'approve'])->name('estimate-review.approve');
Route::post('/estimate-review/{token}/decline', [PublicEstimateReviewController::class, 'decline'])->name('estimate-review.decline');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [BidscapeController::class, 'dashboard'])->name('dashboard');
    Route::get('/leads', [BidscapeController::class, 'leads'])->name('leads.index');
    Route::post('/leads', [BidscapeController::class, 'storeLead'])->name('leads.store');
    Route::put('/leads/{lead}', [BidscapeController::class, 'updateLead'])->name('leads.update');
    Route::post('/leads/{lead}/convert', [WorkflowController::class, 'convertLead'])->name('leads.convert');

    Route::get('/customers', [BidscapeController::class, 'customers'])->name('customers.index');
    Route::post('/customers', [BidscapeController::class, 'storeCustomer'])->name('customers.store');
    Route::put('/customers/{customer}', [BidscapeController::class, 'updateCustomer'])->name('customers.update');

    Route::get('/estimates', [BidscapeController::class, 'estimates'])->name('estimates.index');
    Route::post('/estimates', [BidscapeController::class, 'storeEstimate'])->name('estimates.store');
    Route::get('/estimates/{estimate}/builder', [BidscapeController::class, 'estimateBuilder'])->name('estimates.builder');
    Route::post('/estimates/{estimate}/items', [BidscapeController::class, 'addEstimateItem'])->name('estimates.items.store');
    Route::post('/estimates/{estimate}/send', [BidscapeController::class, 'sendEstimate'])->name('estimates.send');

    Route::get('/jobs', [BidscapeController::class, 'jobs'])->name('jobs.index');
    Route::get('/jobs/{job}/packet', [BidscapeController::class, 'jobPacket'])->name('jobs.packet');
    Route::post('/jobs/{job}/deposits', [WorkflowController::class, 'recordDeposit'])->name('jobs.deposits.store');
    Route::post('/jobs/{job}/packet-ready', [WorkflowController::class, 'markPacketReady'])->name('jobs.packet.ready');
    Route::get('/job-packets/{packet}/pdf', JobPacketPdfController::class)->name('job-packets.pdf');

    Route::get('/assemblies', [BidscapeController::class, 'assemblies'])->name('assemblies.index');
    Route::post('/assemblies', [BidscapeController::class, 'storeAssembly'])->name('assemblies.store');
    Route::put('/assemblies/{assembly}', [BidscapeController::class, 'updateAssembly'])->name('assemblies.update');
    Route::get('/assemblies/{assembly}/formula', [BidscapeController::class, 'assemblyFormula'])->name('assemblies.formula');

    Route::get('/materials', [BidscapeController::class, 'materials'])->name('materials.index');
    Route::post('/materials', [BidscapeController::class, 'storeMaterial'])->name('materials.store');
    Route::put('/materials/{material}', [BidscapeController::class, 'updateMaterial'])->name('materials.update');
    Route::get('/reports', [BidscapeController::class, 'reports'])->name('reports.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/company-profile', [SettingsController::class, 'companyProfile'])->name('settings.company-profile');
    Route::post('/settings/company-profile', [SettingsController::class, 'updateCompanyProfile'])->name('settings.company-profile.update');
    Route::get('/settings/company-profile/logo/{company}', [SettingsController::class, 'companyLogo'])->name('settings.company-logo');
    Route::get('/settings/company-defaults', [SettingsController::class, 'companyDefaults'])->name('settings.company-defaults');
    Route::put('/settings/company-defaults', [SettingsController::class, 'updateCompanyDefaults'])->name('settings.company-defaults.update');
    Route::post('/settings/company-defaults/lead-sources', [SettingsController::class, 'storeLeadSource'])->name('settings.lead-sources.store');
    Route::put('/settings/company-defaults/lead-sources/{source}', [SettingsController::class, 'updateLeadSource'])->name('settings.lead-sources.update');
    Route::get('/settings/price-sheet', [SettingsController::class, 'priceSheet'])->name('settings.price-sheet');
    Route::post('/settings/price-sheet/materials', [SettingsController::class, 'storeMaterial'])->name('settings.price-sheet.materials.store');
    Route::put('/settings/price-sheet/materials/{material}', [SettingsController::class, 'updateMaterial'])->name('settings.price-sheet.materials.update');
    Route::get('/settings/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::post('/attachments', [BidscapeController::class, 'storeAttachment'])->name('attachments.store');

    Route::post('/contracts/{contract}/sign', [WorkflowController::class, 'signContract'])->name('contracts.sign');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
