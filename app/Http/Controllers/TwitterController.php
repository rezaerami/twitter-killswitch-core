<?php

namespace App\Http\Controllers;

use App\Constants\CookieConstants;
use App\Helpers\SecurityHelpers;
use App\Jobs\ReadFriends;
use App\Jobs\ReadTweets;
use App\Models\User;
use Atymic\Twitter\Facade\Twitter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class TwitterController extends Controller
{
    public function login(Request $request)
    {
        $token = Twitter::getRequestToken(route('twitter.callback'));

        if(!isset($token["oauth_token_secret"]))
            throw new \Exception("Unauthorized", 401);


        Session::put("oauth_token", $token["oauth_token"]);
        Session::put("oauth_token_secret", $token["oauth_token_secret"]);

        $url = Twitter::getAuthenticateUrl($token['oauth_token']);
        return Redirect::to($url);
    }

    public function callback(Request $request)
    {
        if(!Session::get("oauth_token"))
            throw new \Exception("Unauthorized", 401);

        $twitter = Twitter::usingCredentials(
            Session::get("oauth_token"),
            Session::get("oauth_token_secret")
        );
        $token = $twitter->getAccessToken($request->oauth_verifier);

        $credentials = [
            "token" => $token["oauth_token"],
            "secret" => $token["oauth_token_secret"],
            "twitter_user_id" => $token["user_id"],
        ];

        $clientData = base64_encode(json_encode($credentials));

        $keyPairs = SecurityHelpers::generateKeyPair();
        $encryptedCredentials = SecurityHelpers::encrypt($clientData, $keyPairs["publicKey"]);

        $privateKey = base64_encode($keyPairs["privateKey"]);
        $user = User::create(["private_key" => $privateKey]);

        $result = [
            "userCode" => SecurityHelpers::idToHashId($user->id),
            "credentials" => base64_encode($encryptedCredentials),
        ];
        $token = base64_encode(json_encode($result));

        $cookie = cookie(
            CookieConstants::TOKEN_COOKIE_NAME,
            $token,
            CookieConstants::TOKEN_COOKIE_MAX_AGE,
            "/",
            null,
            null,
            false
        );
        return redirect(env("FRONTEND_URL"))->withCookie($cookie);
    }

    public function kill(Request $request)
    {
        if(!$request->cookie(CookieConstants::TOKEN_COOKIE_NAME))
            throw new \Exception("Unauthorized", 401);

        $data = $request->cookie(CookieConstants::TOKEN_COOKIE_NAME);

        $userCode = json_decode(base64_decode($data))->userCode;
        $userId = SecurityHelpers::hashIdToId($userCode);
        $user = User::where("id", $userId)->first();
        if(!$user){
            throw new \Exception("Unauthorized", 401);
        }

        $user->updated_at = Carbon::now();
        $user->save();

        ReadTweets::dispatch($data);
        ReadFriends::dispatch($data);
        return response(["status" => "success"]);

    }
}
