<?php

use App\Events\ReverbTestEvent;
use Illuminate\Support\Facades\Route;

Route::get('/reverb-test', fn () => view('reverb-test'))->name('reverb-test');

Route::post('/reverb-test/broadcast', function () {
    $message = request()->input('message', 'Hello from Reverb! '.now()->toTimeString());
    broadcast(new ReverbTestEvent($message));

    return response()->json(['message' => $message]);
})->name('reverb-test.broadcast');
