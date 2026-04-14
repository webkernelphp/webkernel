<?php declare(strict_types=1);

use Webkernel\Platform\SystemPanel\Presentation\Controllers\RootController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\RedirectResponse;

Route::get('/', RootController::class)->name('webkernel.root');


Route::get(
  '__back/{token}',
  fn(string $token): RedirectResponse => redirect()->to(Session::get("origin_{$token}")),
)->where('token', '[a-zA-Z0-9]{16}');
