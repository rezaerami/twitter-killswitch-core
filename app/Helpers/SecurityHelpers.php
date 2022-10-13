<?php


namespace App\Helpers;


use Hashids\Hashids;

class SecurityHelpers
{
    public static function generateKeyPair()
    {
        $keyPair = openssl_pkey_new(["private_key_pairs"]);
        openssl_pkey_export($keyPair, $privateKey);

        $publicKey=openssl_pkey_get_details($keyPair);

        return [
            "publicKey" => $publicKey["key"],
            "privateKey" => $privateKey,
        ];
    }

    public static function encrypt($input, $publicKey)
    {
        openssl_public_encrypt($input, $result, $publicKey);
        return $result;
    }

    public static function decrypt($input, $privateKey)
    {
        openssl_private_decrypt($input, $result, $privateKey);
        return $result;
    }


    /**
     * @param $id
     * @return string
     */
    public static function idToHashId($id)
    {
        $hashId = new Hashids(env('HASHIDS_SALT'));
        return $hashId->encode($id);
    }

    /**
     * @param $code
     * @return mixed
     */
    public static function hashIdToId($code)
    {
        $hashId = new Hashids(env('HASHIDS_SALT'));
        return $hashId->decode($code)[0];
    }
}
