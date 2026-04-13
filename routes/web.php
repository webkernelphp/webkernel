<?php declare(strict_types=1);

use Webkernel\Platform\SystemPanel\Presentation\Controllers\RootController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', RootController::class)->name('webkernel.root');


Route::get('waterfall', function () {
    return view('release.waterfall');
});

$easbPin = '562614';

Route::prefix('demo/eas')->group(function () use ($easbPin) {
    Route::get('/', function (Request $request) use ($easbPin) {
        $token = $request->cookie('easb_token');
        if ($token) {
            $payload = json_decode(decrypt($token), true);
            if ($payload && isset($payload['expires_at']) && now()->timestamp < $payload['expires_at']) {
                return view('cdc.eas');
            }
        }

        return view('cdc.eas-pin', ['pinLength' => strlen($easbPin), 'formAction' => route('easb.pin')]);
    });

    Route::post('/', function (Request $request) use ($easbPin) {
        $durations = [15, 30, 60, 120, 240, 480];
        $minutes = (int) $request->input('duration', 30);
        if (! in_array($minutes, $durations)) {
            $minutes = 30;
        }

        if ($request->input('pin') === $easbPin) {
            $payload = encrypt(json_encode(['expires_at' => now()->addMinutes($minutes)->timestamp]));

            return redirect('/demo/eas')->withCookie(cookie('easb_token', $payload, $minutes));
        }

        return back()->withErrors(['pin' => 'Incorrect PIN']);
    })->name('easb.pin');
});
