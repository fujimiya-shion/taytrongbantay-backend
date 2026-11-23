<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Inbound\Rest\V1\Controllers\AdminController;

Route::prefix('v1')->group(function () {
    // Route::post('admins', [AdminController::class, 'store'])->name('admin.store');
});
