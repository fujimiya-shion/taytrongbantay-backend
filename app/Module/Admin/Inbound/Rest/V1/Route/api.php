<?php

use Illuminate\Support\Facades\Route;
use App\Module\Admin\Inbound\Rest\V1\Controller\AdminController;

Route::prefix('v1')->group(function () {
    // Route::post('admins', [AdminController::class, 'store'])->name('admin.store');
});
