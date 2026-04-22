<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\RadiographerController;
use App\Http\Controllers\ModalityController;
use App\Http\Controllers\ExaminationTypeController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\RadiologyOrderController;
use App\Http\Controllers\RadiologyReportController;
use App\Http\Controllers\RadiologyResultController;
use App\Http\Controllers\RadiologyTemplateController;
use App\Http\Controllers\ViewerController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PACSController;
use App\Http\Controllers\InstitutionSettingController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SimrsController;
use App\Http\Controllers\SimrsModalityMapController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

// Installer Routes
Route::get('/install', [App\Http\Controllers\InstallController::class, 'index'])->name('install.index');
Route::post('/install/update-env', [App\Http\Controllers\InstallController::class, 'updateEnv'])->name('install.update-env');
Route::post('/install/run', [App\Http\Controllers\InstallController::class, 'run'])->name('install.run');

Auth::routes(['register' => false]);

// SIMRS Integration
Route::middleware(['auth', 'role:super_admin,admin_radiologi,radiografer,dokter_radiologi,it_support'])->group(function () {
    Route::get('/simrs/permintaan', [SimrsController::class, 'permintaanRadiologi'])->name('simrs.permintaan');
    Route::get('/simrs/detail/{noorder}', [SimrsController::class, 'detailPermintaan'])->name('simrs.detail');
    Route::post('/simrs/take-sample', [SimrsController::class, 'takeSample'])->name('simrs.take-sample');
    Route::post('/simrs/save-expertise', [SimrsController::class, 'saveExpertise'])->name('simrs.save-expertise');
    Route::get('/simrs/pacs-images/{noorder}', [SimrsController::class, 'getPACSImages'])->name('simrs.pacs-images');
    Route::post('/simrs/update-pacs-acc', [SimrsController::class, 'updatePACSAccession'])->name('simrs.update-pacs-acc');
    Route::post('/simrs/upload-dicom', [SimrsController::class, 'uploadDicom'])->name('simrs.upload-dicom');
    Route::post('/simrs/update-status', [SimrsController::class, 'updateStatus'])->name('simrs.update-status');
    Route::post('/simrs/send-worklist-direct', [SimrsController::class, 'sendToWorklistDirect'])->name('simrs.send-worklist-direct');
    Route::get('/simrs/hasil', [SimrsController::class, 'hasilRadiologi'])->name('simrs.hasil');
    Route::get('/simrs/hasil/print-pdf', [SimrsController::class, 'printHasilPdf'])->name('simrs.hasil.print-pdf');
    Route::get('/simrs/api/templates', [SimrsController::class, 'apiTemplates'])->name('simrs.api.templates');
    Route::post('/simrs/permintaan/send-to-modality', [SimrsController::class, 'sendOrderToModality'])->name('simrs.send-to-modality');

    // Modality Mapping (Admin only)
    Route::middleware(['role:super_admin,admin_radiologi,it_support'])->group(function () {
        Route::get('/simrs/modality-map', [SimrsModalityMapController::class, 'index'])->name('simrs.modality-map.index');
        Route::post('/simrs/modality-map', [SimrsModalityMapController::class, 'store'])->name('simrs.modality-map.store');
        Route::put('/simrs/modality-map/{map}', [SimrsModalityMapController::class, 'update'])->name('simrs.modality-map.update');
        Route::delete('/simrs/modality-map/{map}', [SimrsModalityMapController::class, 'destroy'])->name('simrs.modality-map.destroy');
        Route::post('/simrs/modality-map/import', [SimrsModalityMapController::class, 'importFromSimrs'])->name('simrs.modality-map.import');
    });
});

// Patient Portal (Self-Service)
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PatientPortalController::class, 'index'])->name('index');
    Route::post('/login', [PatientPortalController::class, 'authenticate'])->name('login');
    Route::get('/dashboard', [PatientPortalController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [PatientPortalController::class, 'logout'])->name('logout');
    Route::get('/hasil-radiologi/{token}', [PatientPortalController::class, 'show'])->name('result');
});

// Notifications
Route::middleware(['auth'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/latest', [NotificationController::class, 'getLatest'])->name('notifications.latest');
    Route::post('notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
});

// License Runtime APIs (Global)
Route::get('license-api/runtime/institution', [\App\Http\Controllers\LicenseController::class, 'getInstitutionData'])->name('api.runtime.institution');
Route::post('license-api/request-activation', [\App\Http\Controllers\LicenseController::class, 'requestActivation'])->name('api.license.request-activation');
Route::post('license-api/sync', [\App\Http\Controllers\LicenseController::class, 'syncLicenseData'])->name('api.license.sync');

// Update APIs
Route::get('api/system/check-update', [\App\Http\Controllers\UpdateController::class, 'check'])->name('api.update.check');
Route::post('api/system/run-update', [\App\Http\Controllers\UpdateController::class, 'run'])->middleware('auth')->name('api.update.run');

Route::get('about', [DashboardController::class, 'about'])->name('about');

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public Queue Display (No login required)
Route::prefix('queue')->name('queue.')->group(function () {
    Route::get('/display', [QueueController::class, 'display'])->name('display');
    Route::get('/api/sampling', [QueueController::class, 'apiSampling'])->name('api.sampling');
});

// ========================
// AUTHENTICATED ROUTES
// ========================
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/sync-simrs', [DashboardController::class, 'syncSIMRS'])->name('dashboard.sync-simrs');

    // ========================
    // ADMIN + SUPER ADMIN
    // ========================
    Route::middleware(['role:super_admin,admin_radiologi,it_support'])->group(function () {

        // Master Data
        Route::prefix('master')->name('master.')->group(function () {
            Route::resource('doctors', DoctorController::class)->except('show');
            Route::resource('radiographers', RadiographerController::class)->except('show');
            Route::resource('modalities', ModalityController::class)->except('show');
            Route::resource('examination-types', ExaminationTypeController::class)->except('show');
            Route::resource('rooms', RoomController::class)->except('show');
            Route::resource('templates', RadiologyTemplateController::class)->except('show');

            // Gallery Master
            Route::resource('galleries', \App\Http\Controllers\GalleryController::class);
            Route::post('galleries/{gallery}/activate', [\App\Http\Controllers\GalleryController::class, 'setActive'])->name('galleries.activate');
            Route::get('galleries/{gallery}/items', [\App\Http\Controllers\GalleryController::class, 'manageItems'])->name('galleries.items');
            Route::post('galleries/{gallery}/items', [\App\Http\Controllers\GalleryController::class, 'storeItem'])->name('galleries.items.store');
            Route::delete('galleries/items/{item}', [\App\Http\Controllers\GalleryController::class, 'destroyItem'])->name('galleries.items.destroy');
        });

        // User Management (Super Admin & IT only)
        Route::resource('users', UserController::class)->except('show');

        // Setting Instansi
        Route::get('settings', [InstitutionSettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [InstitutionSettingController::class, 'update'])->name('settings.update');
        Route::post('license/activate', [InstitutionSettingController::class, 'activateLicense'])->name('license.activate');
    });

    // ========================
    // ORDER MANAGEMENT
    // ========================
    Route::middleware(['role:super_admin,admin_radiologi,radiografer'])->group(function () {
        Route::get('/orders/export-csv', [RadiologyOrderController::class, 'exportCsv'])->name('orders.export-csv');
        Route::resource('orders', RadiologyOrderController::class);
        Route::post('orders/{order}/send-worklist', [RadiologyOrderController::class, 'sendToWorklist'])
            ->name('orders.send-worklist');
        Route::post('orders/{order}/take-sample', [RadiologyOrderController::class, 'takeSample'])
            ->name('orders.take-sample');
        Route::post('orders/{order}/start-examination', [RadiologyOrderController::class, 'startExamination'])
            ->name('orders.start-examination');
        Route::post('orders/{order}/complete', [RadiologyOrderController::class, 'completeExamination'])
            ->name('orders.complete');
        Route::post('orders/{order}/input-expertise', [RadiologyOrderController::class, 'inputExpertise'])
            ->name('orders.input-expertise');
        Route::post('orders/{order}/cancel', [RadiologyOrderController::class, 'cancel'])
            ->name('orders.cancel');

        // Staff-only Queue List
        Route::get('queue/sampling', [QueueController::class, 'sampling'])->name('queue.sampling');
    });

    // Orders - view only for doctors
    Route::middleware(['role:super_admin,admin_radiologi,radiografer,dokter_radiologi,it_support'])->group(function () {
        // API for Templates
        Route::get('api/templates/search', [RadiologyTemplateController::class, 'apiSearch'])->name('api.templates.search');
        Route::get('api/templates/{template}/expertise', [RadiologyTemplateController::class, 'apiGetTemplate'])->name('api.templates.expertise');
        Route::get('orders', [RadiologyOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [RadiologyOrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/print', [RadiologyOrderController::class, 'print'])->name('orders.print');
        Route::get('orders/{order}/print-receipt', [RadiologyOrderController::class, 'printReceipt'])->name('orders.print-receipt');

        // Hasil Pemeriksaan (Dedicated view)
        Route::get('results', [RadiologyResultController::class, 'index'])->name('results.index');
        Route::get('results/{order}/edit', [RadiologyResultController::class, 'edit'])->name('results.edit');
        Route::put('results/{order}', [RadiologyResultController::class, 'update'])->name('results.update');
    });

    // ========================
    // PATIENTS
    // ========================
    Route::middleware(['role:super_admin,admin_radiologi,radiografer'])->group(function () {
        Route::resource('patients', PatientController::class);
        Route::get('api/patients/search', [PatientController::class, 'search'])->name('patients.search');
    });

    // ========================
    // REPORTS
    // ========================
    Route::middleware(['role:super_admin,admin_radiologi,dokter_radiologi'])->group(function () {
        Route::get('reports/{order}/create', [RadiologyReportController::class, 'create'])->name('reports.create');
        Route::post('reports/{order}', [RadiologyReportController::class, 'store'])->name('reports.store');
        Route::get('reports/{report}/edit', [RadiologyReportController::class, 'edit'])->name('reports.edit');
        Route::put('reports/{report}', [RadiologyReportController::class, 'update'])->name('reports.update');
        Route::post('reports/{report}/validate', [RadiologyReportController::class, 'validate_report'])->name('reports.validate');
        Route::get('reports/{report}/pdf', [RadiologyReportController::class, 'pdf'])->name('reports.pdf');
    });

    // ========================
    // VIEWER (Dokter Only)
    // ========================
    Route::middleware(['role:super_admin,dokter_radiologi'])->group(function () {
        Route::get('viewer/{order}', [ViewerController::class, 'show'])->name('viewer.show');
    });

    // ========================
    // AUDIT TRAIL (Super Admin)
    // ========================
    Route::middleware(['role:super_admin,it_support'])->group(function () {
        Route::get('audit-trail', [AuditTrailController::class, 'index'])->name('audit.index');
    });

    // ========================
    // PACS MANAGEMENT
    // ========================

    // PACS Read-Only: dokter_radiologi + staff dapat mengakses
    Route::middleware(['role:super_admin,admin_radiologi,radiografer,dokter_radiologi,it_support'])->prefix('pacs')->name('pacs.')->group(function () {
        Route::get('/', [PACSController::class, 'index'])->name('index');
        Route::get('/patients', [PACSController::class, 'patients'])->name('patients');
        Route::get('/patients/{id}', [PACSController::class, 'patientDetail'])->name('patient-detail');
        Route::get('/studies', [PACSController::class, 'studies'])->name('studies');
        Route::post('/studies/send-to-modality', [PACSController::class, 'sendToModality'])->name('send-to-modality');
        Route::get('/studies/{id}/detail', [PACSController::class, 'studyDetail'])->name('study-detail');
        Route::get('/series/{id}', [PACSController::class, 'seriesDetail'])->name('series-detail');
        Route::get('/viewer/{id}', [PACSController::class, 'viewer'])->name('viewer');
        Route::get('/search', [PACSController::class, 'search'])->name('search');
        Route::get('/instances/{id}/preview', [PACSController::class, 'instancePreview'])->name('instance-preview');
    });

    // PACS Admin-Only: hanya staff (bukan dokter)
    Route::middleware(['role:super_admin,admin_radiologi,radiografer,it_support'])->prefix('pacs')->name('pacs.')->group(function () {
        Route::get('/patients/sync-all', [PACSController::class, 'syncAllPatients'])->name('sync-all-patients');
        Route::post('/patients/{id}/sync', [PACSController::class, 'syncPatient'])->name('sync-patient');
        Route::delete('/patients/{id}', [PACSController::class, 'deletePatient'])->name('delete-patient');
        Route::post('/studies/{id}/modify', [PACSController::class, 'modifyStudy'])->name('modify-study');
        Route::delete('/studies/{id}', [PACSController::class, 'deleteStudy'])->name('delete-study');
        Route::get('/upload', [PACSController::class, 'upload'])->name('upload');
        Route::post('/upload', [PACSController::class, 'storeUpload'])->name('store-upload');
        Route::get('/modalities', [PACSController::class, 'modalities'])->name('modalities');
        Route::post('/modalities', [PACSController::class, 'storeModality'])->name('store-modality');
        Route::delete('/modalities/{name}', [PACSController::class, 'destroyModality'])->name('destroy-modality');
        Route::get('/worklists', [PACSController::class, 'worklists'])->name('worklists');
    });

    // ========================
    // SECURE ORTHANC PROXY
    // ========================
    $orthancPrefixes = ['ohif', 'dicom-web', 'wado', 'instances', 'studies', 'series', 'patients', 'app', 'osimis-viewer', 'stone-webviewer', 'worklists'];
    foreach ($orthancPrefixes as $prefix) {
        Route::any("/{$prefix}/{path?}", [\App\Http\Controllers\OrthancController::class, 'forward'])->where('path', '.*');
    }

    // ========================
    // ANALYTICS & REPORTS
    // ========================
    Route::middleware(['role:super_admin,admin_radiologi,direktur'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/duration', [ReportController::class, 'durationReport'])->name('duration');
        Route::get('/examination', [ReportController::class, 'examinationReport'])->name('examination');
        Route::get('/duration-by-exam', [ReportController::class, 'durationByExamination'])->name('duration-by-exam');
        Route::get('/requests', [ReportController::class, 'requestReport'])->name('requests');
    });

    // ========================
    // SATUSEHAT INTEGRATION
    // ========================
    Route::middleware(['role:super_admin,admin_radiologi'])->prefix('satusehat')->name('satusehat.')->group(function () {
        // ─── MENU BARU: Kirim Request (standalone, exact port dari eradiologi) ───
        Route::get('/kirim-request',  [\App\Http\Controllers\KirimRequestController::class, 'index'])->name('kirim-request');
        Route::post('/kirim-request/tampil', [\App\Http\Controllers\KirimRequestController::class, 'tampil'])->name('kirim-request.tampil');
        Route::post('/kirim-request/post',   [\App\Http\Controllers\KirimRequestController::class, 'post'])->name('kirim-request.post');
        Route::post('/kirim-request/put',    [\App\Http\Controllers\KirimRequestController::class, 'put'])->name('kirim-request.put');

        // ─── MENU BARU: Kirim Service Request (SIMRS Bridging) ───
        Route::get('/kirim-servicerequest',  [\App\Http\Controllers\KirimServiceRequestController::class, 'index'])->name('kirim-servicerequest');
        Route::post('/kirim-servicerequest/tampil', [\App\Http\Controllers\KirimServiceRequestController::class, 'tampil'])->name('kirim-servicerequest.tampil');
        Route::post('/kirim-servicerequest/post',   [\App\Http\Controllers\KirimServiceRequestController::class, 'post'])->name('kirim-servicerequest.post');

        // ─── MENU BARU: Kirim Encounter (SIMRS Bridging) ───
        Route::get('/kirim-encounter',  [\App\Http\Controllers\KirimEncounterController::class, 'index'])->name('kirim-encounter');
        Route::post('/kirim-encounter/tampil', [\App\Http\Controllers\KirimEncounterController::class, 'tampil'])->name('kirim-encounter.tampil');
        Route::post('/kirim-encounter/post',   [\App\Http\Controllers\KirimEncounterController::class, 'post'])->name('kirim-encounter.post');
        Route::post('/kirim-encounter/finish', [\App\Http\Controllers\KirimEncounterController::class, 'finish'])->name('kirim-encounter.finish');

        // ─── MENU BARU: Kirim Specimen (SIMRS Bridging) ───
        Route::get('/kirim-specimen',  [\App\Http\Controllers\KirimSpecimenController::class, 'index'])->name('kirim-specimen');
        Route::post('/kirim-specimen/tampil', [\App\Http\Controllers\KirimSpecimenController::class, 'tampil'])->name('kirim-specimen.tampil');
        Route::post('/kirim-specimen/post',   [\App\Http\Controllers\KirimSpecimenController::class, 'post'])->name('kirim-specimen.post');

        // ─── MENU BARU: Kirim Imaging Study (SIMRS Bridging) ───
        Route::get('/kirim-imaging',  [\App\Http\Controllers\KirimImagingStudyController::class, 'index'])->name('kirim-imaging');
        Route::post('/kirim-imaging/tampil', [\App\Http\Controllers\KirimImagingStudyController::class, 'tampil'])->name('kirim-imaging.tampil');
        Route::post('/kirim-imaging/post',   [\App\Http\Controllers\KirimImagingStudyController::class, 'post'])->name('kirim-imaging.post');

        // New Modules from Java Reference
        Route::get('/kirim-allergy', [\App\Http\Controllers\KirimAllergyController::class, 'index'])->name('kirim-allergy');
        Route::post('/kirim-allergy/post', [\App\Http\Controllers\KirimAllergyController::class, 'post'])->name('kirim-allergy.post');

        Route::get('/kirim-episodeofcare', [\App\Http\Controllers\KirimEpisodeOfCareController::class, 'index'])->name('kirim-episodeofcare');
        Route::post('/kirim-episodeofcare/post', [\App\Http\Controllers\KirimEpisodeOfCareController::class, 'post'])->name('kirim-episodeofcare.post');

        Route::get('/kirim-medication', [\App\Http\Controllers\KirimMedicationController::class, 'index'])->name('kirim-medication');
        Route::post('/kirim-medication/post', [\App\Http\Controllers\KirimMedicationController::class, 'post'])->name('kirim-medication.post');

        Route::get('/kirim-vaksin', [\App\Http\Controllers\KirimVaksinController::class, 'index'])->name('kirim-vaksin');
        Route::post('/kirim-vaksin/post', [\App\Http\Controllers\KirimVaksinController::class, 'post'])->name('kirim-vaksin.post');
        
        Route::get('/kirim-vaksin-ori', [\App\Http\Controllers\KirimVaksinOriController::class, 'index'])->name('kirim-vaksin-ori');
        Route::post('/kirim-vaksin-ori/post', [\App\Http\Controllers\KirimVaksinOriController::class, 'post'])->name('kirim-vaksin-ori.post');

        Route::get('/mapping-episodeofcare', [\App\Http\Controllers\MappingEpisodeOfCareController::class, 'index'])->name('mapping-episodeofcare');
        Route::post('/mapping-episodeofcare/post', [\App\Http\Controllers\MappingEpisodeOfCareController::class, 'post'])->name('mapping-episodeofcare.post');
        Route::delete('/mapping-episodeofcare/destroy', [\App\Http\Controllers\MappingEpisodeOfCareController::class, 'destroy'])->name('mapping-episodeofcare.destroy');
        Route::get('/mapping-episodeofcare/search-penyakit', [\App\Http\Controllers\MappingEpisodeOfCareController::class, 'searchPenyakit'])->name('mapping-episodeofcare.search-penyakit');

        Route::get('/mapping-allergy', [\App\Http\Controllers\MappingAllergyController::class, 'index'])->name('mapping-allergy');
        Route::post('/mapping-allergy/store', [\App\Http\Controllers\MappingAllergyController::class, 'store'])->name('mapping-allergy.store');
        Route::delete('/mapping-allergy/destroy', [\App\Http\Controllers\MappingAllergyController::class, 'destroy'])->name('mapping-allergy.destroy');

        // ─── MENU BARU: Kirim Observation (SIMRS Bridging) ───
        Route::get('/kirim-observation',  [\App\Http\Controllers\KirimObservationController::class, 'index'])->name('kirim-observation');
        Route::post('/kirim-observation/tampil', [\App\Http\Controllers\KirimObservationController::class, 'tampil'])->name('kirim-observation.tampil');
        Route::post('/kirim-observation/post',   [\App\Http\Controllers\KirimObservationController::class, 'post'])->name('kirim-observation.post');

        // ─── MENU BARU: Kirim Diagnostic Report (SIMRS Bridging) ───
        Route::get('/kirim-diagnosticreport',  [\App\Http\Controllers\KirimDiagnosticReportController::class, 'index'])->name('kirim-diagnosticreport');
        Route::post('/kirim-diagnosticreport/tampil', [\App\Http\Controllers\KirimDiagnosticReportController::class, 'tampil'])->name('kirim-diagnosticreport.tampil');
        Route::post('/kirim-diagnosticreport/post',   [\App\Http\Controllers\KirimDiagnosticReportController::class, 'post'])->name('kirim-diagnosticreport.post');

        Route::get('/kirim-condition', [\App\Http\Controllers\KirimConditionController::class, 'index'])->name('kirim-condition');
        Route::post('/kirim-condition/post', [\App\Http\Controllers\KirimConditionController::class, 'post'])->name('kirim-condition.post');

        Route::get('/kirim-procedure', [\App\Http\Controllers\KirimProcedureController::class, 'index'])->name('kirim-procedure');
        Route::post('/kirim-procedure/post', [\App\Http\Controllers\KirimProcedureController::class, 'post'])->name('kirim-procedure.post');

        Route::get('/kirim-observation-ttv', [\App\Http\Controllers\KirimObservationTTVController::class, 'index'])->name('kirim-observation-ttv');
        Route::post('/kirim-observation-ttv/post', [\App\Http\Controllers\KirimObservationTTVController::class, 'post'])->name('kirim-observation-ttv.post');

        Route::get('/mapping-laborat', [\App\Http\Controllers\MappingLaboratController::class, 'index'])->name('mapping-laborat');
        Route::post('/mapping-laborat', [\App\Http\Controllers\MappingLaboratController::class, 'store'])->name('mapping-laborat.store');
        Route::delete('/mapping-laborat/{id}', [\App\Http\Controllers\MappingLaboratController::class, 'destroy'])->name('mapping-laborat.destroy');

        Route::get('/kirim-observation-lab-pk', [\App\Http\Controllers\KirimObservationLabPKController::class, 'index'])->name('kirim-observation-lab-pk');
        Route::post('/kirim-observation-lab-pk/post', [\App\Http\Controllers\KirimObservationLabPKController::class, 'post'])->name('kirim-observation-lab-pk.post');

        Route::get('/mapping-obat', [\App\Http\Controllers\MappingObatController::class, 'index'])->name('mapping-obat');
        Route::post('/mapping-obat', [\App\Http\Controllers\MappingObatController::class, 'store'])->name('mapping-obat.store');
        Route::delete('/mapping-obat/{id}', [\App\Http\Controllers\MappingObatController::class, 'destroy'])->name('mapping-obat.destroy');

        Route::get('/mapping-radiologi', [\App\Http\Controllers\MappingRadiologiController::class, 'index'])->name('mapping-radiologi');
        Route::post('/mapping-radiologi', [\App\Http\Controllers\MappingRadiologiController::class, 'store'])->name('mapping-radiologi.store');
        Route::delete('/mapping-radiologi/{id}', [\App\Http\Controllers\MappingRadiologiController::class, 'destroy'])->name('mapping-radiologi.destroy');

        Route::get('/mapping-vaksin', [\App\Http\Controllers\MappingVaksinController::class, 'index'])->name('mapping-vaksin');
        Route::post('/mapping-vaksin', [\App\Http\Controllers\MappingVaksinController::class, 'store'])->name('mapping-vaksin.store');
        Route::delete('/mapping-vaksin/{id}', [\App\Http\Controllers\MappingVaksinController::class, 'destroy'])->name('mapping-vaksin.destroy');

        Route::get('/mapping-lokasi', [\App\Http\Controllers\MappingLokasiController::class, 'index'])->name('mapping-lokasi');
        Route::post('/mapping-lokasi', [\App\Http\Controllers\MappingLokasiController::class, 'store'])->name('mapping-lokasi.store');

        Route::get('/mapping-organisasi', [\App\Http\Controllers\MappingOrganisasiController::class, 'index'])->name('mapping-organisasi');
        Route::post('/mapping-organisasi', [\App\Http\Controllers\MappingOrganisasiController::class, 'store'])->name('mapping-organisasi.store');
    });



    // Region APIs
    Route::prefix('regions')->name('regions.')->group(function () {
        Route::get('/provinces', [\App\Http\Controllers\RegionController::class, 'provinces'])->name('provinces');
        Route::get('/regencies/{province_id}', [\App\Http\Controllers\RegionController::class, 'regencies'])->name('regencies');
        Route::get('/districts/{regency_id}', [\App\Http\Controllers\RegionController::class, 'districts'])->name('districts');
        Route::get('/villages/{district_id}', [\App\Http\Controllers\RegionController::class, 'villages'])->name('villages');
    });

});
