<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TwitterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([
    "prefix" => "twitter",
    "middleware" => "throttle:100,1",
], function () {
    Route::get('/login', [TwitterController::Class, "login"])->name("twitter.login");
    Route::get('/callback', [TwitterController::Class, "callback"])->name("twitter.callback");
});
