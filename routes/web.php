<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/what-we-do', [PageController::class, 'whatWeDo'])->name('what-we-do');
Route::get('/childrens-corner', [PageController::class, 'childrensCorner'])->name('childrens-corner');
Route::get('/reporting', [PageController::class, 'reporting'])->name('reporting');
Route::post('/reporting', [PageController::class, 'storeReport'])->name('reporting.store');
Route::post('/reporting/evidence', [PageController::class, 'storeEvidence'])->name('reporting.evidence.store');
Route::get('/resources', [PageController::class, 'resources'])->name('resources');
Route::get('/resources/{slug}/download', [PageController::class, 'downloadResource'])->name('resources.download');
Route::get('/resources/{slug}', [PageController::class, 'resourceDetail'])->name('resources.show');
Route::get('/donate', [PageController::class, 'donate'])->name('donate');
Route::get('/advertise', [PageController::class, 'advertise'])->name('advertise');
Route::post('/advertise/inquire', [PageController::class, 'storeAdvertiseInquiry'])->name('advertise.inquire');
Route::get('/latest-news', [PageController::class, 'latestNews'])->name('latest-news');
Route::get('/news', function () { return redirect()->route('latest-news', [], 301); })->name('news');
Route::get('/news/{slug}', [PageController::class, 'newsArticle'])->name('news.show');
Route::get('/checkout', [PageController::class, 'checkout'])->name('checkout');


Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
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
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::put('/media/{asset}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{asset}', [MediaController::class, 'destroy'])->name('media.destroy');
});
