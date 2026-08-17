<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PropertyAiExtractionController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyImageController;
use App\Http\Controllers\PropertyTypeController;
use App\Http\Controllers\PublicPropertyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catálogo público (sin autenticación)
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicPropertyController::class, 'index'])->name('home');
Route::get('/propiedades', [PublicPropertyController::class, 'index'])->name('public.properties.index');
Route::get('/propiedades/{property:slug}', [PublicPropertyController::class, 'show'])->name('public.properties.show');

/*
|--------------------------------------------------------------------------
| Invitados
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/dologin', [LoginController::class, 'login'])->name('login.post');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
});

/*
|--------------------------------------------------------------------------
| Autenticados
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Propiedades: la PropertyPolicy resuelve "sólo las mías" para el rol Agent.
    Route::resource('properties', PropertyController::class);
    Route::post('properties/{property}/publish', [PropertyController::class, 'togglePublish'])
        ->name('properties.publish');
    Route::post('properties-ai-extract', PropertyAiExtractionController::class)
        ->name('properties.ai-extract');

    // Imágenes
    Route::post('properties/{property}/images', [PropertyImageController::class, 'store'])
        ->name('properties.images.store');
    Route::delete('properties/{property}/images/{image}', [PropertyImageController::class, 'destroy'])
        ->name('properties.images.destroy');
    Route::post('properties/{property}/images/reorder', [PropertyImageController::class, 'reorder'])
        ->name('properties.images.reorder');

    // Catálogos
    Route::middleware('can:catalogs.manage')->group(function () {
        Route::resource('property-types', PropertyTypeController::class)->except('show');
        Route::resource('features', FeatureController::class)->except('show');
    });

    // Administración de accesos
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class)->except('show');
});
