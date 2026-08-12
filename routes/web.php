<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentPreviewController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\SecureDownloadController;
use App\Http\Controllers\VerifyDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

require __DIR__.'/auth.php';

// Halaman institusi (Sambutan Ketua, Tentang DMS, dst.) — dikelola lewat
// PageResource di admin, ditampilkan di navbar (lihat layouts/app.blade.php).
Route::get('/page/{page:slug}', [PageController::class, 'show'])->name('pages.show');

// Poin 23/24 — public document catalog (guest-visible where permitted, filtered
// per-user via Document::scopeVisibleTo / DocumentPolicy).
Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/search', [DocumentController::class, 'index'])->name('documents.search');
Route::get('/documents/{document:uuid}', [DocumentController::class, 'show'])->name('documents.show');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category:code}', [CategoryController::class, 'show'])->name('categories.show');

// Poin 13/17 — secure, authorization-gated file access. Never a direct storage URL.
Route::get('/documents/{document:uuid}/preview', [DocumentPreviewController::class, '__invoke'])
    ->name('documents.preview');
Route::get('/documents/{document:uuid}/preview/{version}', [DocumentPreviewController::class, '__invoke'])
    ->name('documents.preview.version');

Route::get('/documents/{document:uuid}/download', [SecureDownloadController::class, '__invoke'])
    ->name('documents.download');
Route::get('/documents/{document:uuid}/download/{version}', [SecureDownloadController::class, '__invoke'])
    ->name('documents.download.version');

// Poin 25 — QR verification, deliberately public/unauthenticated.
Route::get('/verify-document/{uuid}', VerifyDocumentController::class)->name('verify-document');
Route::get('/documents/{document:uuid}/qr-code', QrCodeController::class)->name('documents.qr-code');
