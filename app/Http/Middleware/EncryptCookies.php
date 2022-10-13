<?php

namespace App\Http\Middleware;

use App\Constants\CookieConstants;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        CookieConstants::TOKEN_COOKIE_NAME,
    ];
}
