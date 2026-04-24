<?php declare(strict_types=1);

use Webkernel\Routes\RootController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\RedirectResponse;

Route::get('/', RootController::class)->name('webkernel.root');

//Route::get('welcome', fn() => view('welcome'))->name('webkernel.welcome');

Route::get(
  '__back/{token}',
  fn(string $token): RedirectResponse => redirect()->to(Session::get("origin_{$token}")),
)->where('token', '[a-zA-Z0-9]{16}');
