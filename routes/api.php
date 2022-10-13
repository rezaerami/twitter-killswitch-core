<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TwitterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    "prefix" => "twitter",
    "middleware" => "throttle:100,1",
], function () {
    Route::get('/kill', [TwitterController::Class, "kill"])->name("twitter.kill");
});
