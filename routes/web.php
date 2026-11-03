<?php

use App\Http\Controllers\Admin\Access\AuditLogController;
use App\Http\Controllers\Admin\Access\RoleController;
use App\Http\Controllers\Admin\Access\UserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CaseController;
use App\Http\Controllers\Admin\CaseTotpController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReportingController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CaseTotpMiddleware;
use App\Http\Middleware\CmsMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/what-we-do', [PageController::class, 'whatWeDo'])->name('what-we-do');
Route::get('/childrens-corner', [PageController::class, 'childrensCorner'])->name('childrens-corner');
Route::get('/reporting', [ReportingController::class, 'index'])->name('reporting');
Route::post('/reporting', [ReportingController::class, 'store'])->middleware('throttle:5,1')->name('reporting.store');
Route::post('/reporting/track', [ReportingController::class, 'lookup'])->middleware('throttle:10,1')->name('reporting.track');
Route::post('/reporting/track/logout', [ReportingController::class, 'logout'])->name('reporting.track.logout');
Route::post('/reporting/evidence', [ReportingController::class, 'storeEvidence'])->middleware('throttle:10,1')->name('reporting.evidence.store');
Route::get('/reporting/evidence/{evidence}', [ReportingController::class, 'downloadEvidence'])->middleware('throttle:30,1')->name('reporting.evidence.download');
Route::get('/resources', [PageController::class, 'resources'])->name('resources');
Route::get('/resources/{slug}/download', [PageController::class, 'downloadResource'])->name('resources.download');
Route::get('/resources/{slug}', [PageController::class, 'resourceDetail'])->name('resources.show');
Route::get('/donate', [PageController::class, 'donate'])->name('donate');
Route::get('/advertise', [PageController::class, 'advertise'])->name('advertise');
Route::post('/advertise/inquire', [PageController::class, 'storeAdvertiseInquiry'])->middleware('throttle:10,1')->name('advertise.inquire');
Route::get('/latest-news', [PageController::class, 'latestNews'])->name('latest-news');
Route::redirect('/news', '/latest-news', 301)->name('news');
Route::get('/news/{slug}', [PageController::class, 'newsArticle'])->name('news.show');
Route::get('/checkout', [PageController::class, 'checkout'])->name('checkout');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:10,1')->name('newsletter.store');

Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    /*
     * Authenticator setup and challenge. These routes sit outside the TOTP gate
     * so staff can enrol before it, but still require an admin-capable account.
     */
    Route::get('/cases/totp/setup', [CaseTotpController::class, 'showSetup'])->name('cases.totp.setup');
    Route::post('/cases/totp/setup', [CaseTotpController::class, 'confirmSetup'])->name('cases.totp.confirm');
    Route::get('/cases/totp/challenge', [CaseTotpController::class, 'showChallenge'])->name('cases.totp.challenge');
    Route::post('/cases/totp/challenge', [CaseTotpController::class, 'challenge'])->name('cases.totp.verify');

    /*
     * Every route below requires a recent TOTP challenge for users whose roles
     * require it. CMS-only roles pass straight through.
     */
    Route::middleware([CaseTotpMiddleware::class])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('cases')->name('cases.')->group(function () {
            Route::get('/', [CaseController::class, 'index'])->name('index');
            Route::get('/{report}', [CaseController::class, 'show'])->name('show');
            Route::get('/{report}/audit', [CaseController::class, 'audit'])->name('audit');
            Route::post('/{report}/assign', [CaseController::class, 'assign'])->name('assign');
            Route::post('/{report}/status', [CaseController::class, 'updateStatus'])->name('status');
            Route::post('/{report}/notes', [CaseController::class, 'storeNote'])->name('notes.store');
            Route::post('/{report}/evidence', [CaseController::class, 'storeEvidence'])->name('evidence.store');
            Route::get('/{report}/evidence/{evidence}', [CaseController::class, 'downloadEvidence'])->name('evidence.download');
        });

        Route::prefix('access')->name('access.')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
            Route::post('/users/{user}/reset-two-factor', [UserController::class, 'resetTwoFactor'])->name('users.reset-two-factor');

            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

            Route::get('/audit', [AuditLogController::class, 'index'])->name('audit');
        });

        /*
         * Website content is only available to CMS-capable roles. Case staff are
         * blocked here even though they can authenticate to the admin area.
         */
        Route::middleware([CmsMiddleware::class])->group(function () {
            Route::get('/contents/{section}', [ContentController::class, 'index'])->name('contents.index');
            Route::get('/contents/{section}/create', [ContentController::class, 'create'])->name('contents.create');
            Route::post('/contents/{section}', [ContentController::class, 'store'])->name('contents.store');
            Route::post('/contents/{section}/bulk', [ContentController::class, 'bulkAction'])->name('contents.bulk');
            Route::post('/contents/{section}/reorder', [ContentController::class, 'reorder'])->name('contents.reorder');
            Route::get('/contents/{section}/{id}/edit', [ContentController::class, 'edit'])->name('contents.edit');
            Route::get('/contents/{section}/{id}/preview', [ContentController::class, 'preview'])->name('contents.preview');
            Route::post('/contents/{section}/{id}/revisions/{revision}/restore', [ContentController::class, 'restoreRevision'])->name('contents.revisions.restore');
            Route::put('/contents/{section}/{id}', [ContentController::class, 'update'])->name('contents.update');
            Route::delete('/contents/{section}/{id}', [ContentController::class, 'destroy'])->name('contents.destroy');
            Route::post('/contents/{section}/{id}/toggle-publish', [ContentController::class, 'togglePublish'])->name('contents.toggle-publish');
            Route::post('/contents/{id}/upload-image', [ContentController::class, 'uploadImage'])->name('contents.upload-image');
            Route::get('/enquiries/{id}/review', [EnquiryController::class, 'review'])->name('enquiries.review');
            Route::post('/enquiries/{id}/approve', [EnquiryController::class, 'approve'])->name('enquiries.approve');
            Route::post('/enquiries/{id}/reject', [EnquiryController::class, 'reject'])->name('enquiries.reject');
            Route::post('/enquiries/{id}/renew', [EnquiryController::class, 'renew'])->name('enquiries.renew');
            Route::get('/media', [MediaController::class, 'index'])->name('media.index');
            Route::post('/media', [MediaController::class, 'store'])->name('media.store');
            Route::put('/media/{asset}', [MediaController::class, 'update'])->name('media.update');
            Route::delete('/media/{asset}', [MediaController::class, 'destroy'])->name('media.destroy');

            Route::get('/newsletter', [AdminNewsletterController::class, 'index'])->name('newsletter.index');
            Route::get('/newsletter/export', [AdminNewsletterController::class, 'export'])->name('newsletter.export');
            Route::delete('/newsletter/{subscriber}', [AdminNewsletterController::class, 'destroy'])->name('newsletter.destroy');
        });
    });
});
