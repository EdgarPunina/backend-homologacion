<?php

use App\Http\Controllers\Api\Admin\CareerController;
use App\Http\Controllers\Api\Admin\CoordinatorCareerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ResolutionController;
use App\Http\Controllers\Api\Admin\SolicitudController;
use App\Http\Controllers\Api\Admin\StudentController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API backend operativa',
        'data' => [
            'service' => 'backend-homologacion',
            'status' => 'ok',
        ],
    ]);
});

Route::prefix('v1')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/roles', [AuthController::class, 'roles'])->middleware('role:Administrador');

        Route::prefix('admin')->name('admin.')->middleware('role:Administrador')->group(function (): void {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');

            Route::get('/coordinators/{coordinator}/careers', [CoordinatorCareerController::class, 'index'])->name('coordinators.careers.index');
            Route::put('/coordinators/{coordinator}/careers', [CoordinatorCareerController::class, 'update'])->name('coordinators.careers.update');
            Route::get('/careers', CareerController::class)->name('careers.index');

            Route::get('/students', [StudentController::class, 'index'])->name('students.index');
            Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

            Route::get('/solicitudes', [SolicitudController::class, 'index'])->name('solicitudes.index');
            Route::get('/solicitudes/{solicitud}', [SolicitudController::class, 'show'])->name('solicitudes.show');
            Route::post('/solicitudes/{solicitud}/resolucion', [ResolutionController::class, 'store'])->name('solicitudes.resolucion.store');
            Route::get('/solicitudes/{solicitud}/resolucion/download', [ResolutionController::class, 'download'])->name('solicitudes.resolucion.download');

            Route::get('/reports/solicitudes', [ReportController::class, 'solicitudes'])->name('reports.solicitudes');
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
        });
    });
});
