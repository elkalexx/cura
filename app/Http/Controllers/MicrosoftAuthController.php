<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftAuthController
{
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');

        Setting::updateOrCreate(
            ['path' => 'ms.app_token'],
            ['secret_value' => $code]
        );

        $this->requestAccessToken($code);
    }

    public function requestAccessToken(string $code)
    {
        $tenant = Config::string('services.ms.tenantId');
        $clientId = Config::string('services.ms.clientId');
        $grantType = 'authorization_code';
        $scope = Config::string('services.ms.requestAccessTokenScope');
        $redirectUrl = Config::string('app.url').Config::string('services.ms.redirectUri');
        $clientSecret = Config::string('services.ms.clientSecret');

        $result = Http::asForm()
            ->post('https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token',
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => $grantType,
                    'code' => $code,
                    'redirect_uri' => $redirectUrl,
                    'scope' => $scope,
                ]
            );

        $tokens = json_decode($result->body());

        Log::info(var_export($tokens, true));

        $expiresInTimestamp = now()->addHours(2)->timestamp;

        Setting::updateOrCreate(
            ['path' => 'ms.access_token'],
            ['secret_value' => $tokens->access_token]
        );

        Setting::updateOrCreate(
            ['path' => 'ms.refresh_token'],
            ['secret_value' => $tokens->refresh_token]
        );

        Setting::updateOrCreate(
            ['path' => 'ms.token_expires_in'],
            ['value' => $expiresInTimestamp]
        );
    }

    public function redirectToMicrosoft()
    {
        $state = bin2hex(random_bytes(16));

        $scopes = Config::string('services.ms.authAppScope');
        $clientId = Config::string('services.ms.clientId');
        $tenantId = Config::string('services.ms.tenantId');
        $query = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'response_mode' => 'query',
            'scope' => $scopes,
            'state' => $state,
        ]);

        return redirect()->away(
            'https://login.microsoft.com/'.$tenantId.'/oauth2/v2.0/authorize?'.$query
        );
    }
}
