<?php

namespace App\Http\Controllers\Web;

use App\FatSecret\FatSecretException;
use App\FatSecret\FatSecretFacade;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;

class UserSetFatSecretTokenController extends Controller
{
    /**
     * @throws GuzzleException
     */
    public function __invoke(User $user, FatSecretFacade $fatSecretFacade)
    {
        try {
            $fatSecretFacade->getRequestToken();
        } catch (\Exception $e) {
            return view('settings')
                ->withErrors(['error' => $e->getMessage()])
                ->with(['user' => $user]);
        }
        return view('settings')
            ->with(['user' => $user]);
    }

    private function test($user)
    {
        $base_url = "https://platform.fatsecret.com/rest/server.api";
        $method = "GET";
        $parameters = array(
            "method" => "weights.get_month",
//            "date" => strtotime("2023-03-01"),
            "date" => 19416,
            "format" => "json",
            "oauth_consumer_key" => config('services.fatsecret.key'),
            "oauth_nonce" => md5(microtime() . mt_rand()),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_timestamp" => time(),
            "oauth_token" => $user->oauth_token,
            "oauth_version" => "1.0"
        );

// Sort the parameters alphabetically
        ksort($parameters);

// Encode the parameters for the signature
        $encoded_parameters = "";
        foreach ($parameters as $key => $value) {
            $encoded_parameters .= $key . "=" . rawurlencode($value) . "&";
        }
        $encoded_parameters = rtrim($encoded_parameters, "&");

// Build the signature base string
        $signature_base_string = $method . "&" . rawurlencode($base_url) . "&" . rawurlencode($encoded_parameters);

// Create the signing key
        $signing_key = config('services.fatsecret.secret') ."&" . $user->oauth_token_secret;

// Calculate the signature
        $signature = base64_encode(hash_hmac("sha1", $signature_base_string, $signing_key, true));

// Add the signature to the parameters
        $parameters["oauth_signature"] = $signature;

// Sort the parameters alphabetically (again)
        ksort($parameters);

// Build the final URL
        $url = $base_url . "?" . http_build_query($parameters);

// Make the request
        $result = file_get_contents($url);

// Decode the result
        $result = json_decode($result);

// Print the result
        print_r($result);
    }

}
//$temporaryCredentials->setIdentifier($data['oauth_token']);
//$temporaryCredentials->setSecret($data['oauth_token_secret']);
//0a496f5c39a6495babc3ad6b317e6f19
