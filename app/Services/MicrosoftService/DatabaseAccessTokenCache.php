<?php

namespace App\Services\MicrosoftService;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Token\AccessToken;
use Microsoft\Kiota\Authentication\Cache\AccessTokenCache;

class DatabaseAccessTokenCache implements AccessTokenCache
{
    public function getAccessToken(string $identity): ?AccessToken
    {
        // Read the actual scalar values (not builders)
        $accessToken = Setting::where('path', 'ms.access_token')->value('secret_value');
        $refreshToken = Setting::where('path', 'ms.refresh_token')->value('secret_value');
        $expiresTimestamp = Setting::where('path', 'ms.token_expires_in')->value('value');

        if (empty($accessToken) || empty($expiresTimestamp)) {
            Log::warning('Access token or expiry missing for identity: '.$identity);

            return null;
        }

        return new AccessToken([
            'access_token' => (string) $accessToken,
            // refresh token may be null/empty depending on the flow — that’s fine
            'refresh_token' => $refreshToken ?: null,
            // must be an int (epoch seconds or seconds-from-now; here we store epoch)
            'expires' => (int) $expiresTimestamp,
        ]);
    }

    public function persistAccessToken(string $identity, AccessToken $accessToken): void
    {
        Setting::updateOrCreate(
            ['path' => 'ms.access_token'],
            ['secret_value' => $accessToken->getToken()]
        );

        if ($accessToken->getRefreshToken()) {
            Setting::updateOrCreate(
                ['path' => 'ms.refresh_token'],
                ['secret_value' => $accessToken->getRefreshToken()]
            );
        }

        if ($accessToken->getExpires()) {
            // Store epoch seconds as an integer-like string/value
            Setting::updateOrCreate(
                ['path' => 'ms.token_expires_in'],
                ['value' => $accessToken->getExpires()]
            );
        }
    }
}
