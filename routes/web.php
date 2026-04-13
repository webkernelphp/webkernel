<?php declare(strict_types=1);

use Webkernel\Platform\SystemPanel\Presentation\Controllers\RootController;

use Illuminate\Support\Facades\Route;

Route::get('/', RootController::class)->name('webkernel.root');
