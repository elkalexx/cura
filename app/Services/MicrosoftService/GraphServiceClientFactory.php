<?php

namespace App\Services\MicrosoftService;

use Illuminate\Support\Facades\Config;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\AuthorizationCodeContext;

class GraphServiceClientFactory
{
    public function createGraphServiceClient()
    {
        $microsoftTenantId = Config::string('services.ms.tenantId');
        $microsoftClientId = Config::string('services.ms.clientId');
        $microsoftClientSecret = Config::string('services.ms.clientSecret');
        $redirectUrl = Config::string('app.url').Config::string('services.ms.redirectUri');

        $tokenRequestContext = new AuthorizationCodeContext(
            $microsoftTenantId,
            $microsoftClientId,
            $microsoftClientSecret,
            'placeholder',
            $redirectUrl,
        );

        $tokenCache = new DatabaseAccessTokenCache;

        $tokenRequestContext->setCacheKey($tokenCache->getAccessToken(''));

        $authProvider = GraphPhpLeagueAuthenticationProvider::createWithAccessTokenProvider(
            GraphPhpLeagueAccessTokenProvider::createWithCache(
                $tokenCache,
                $tokenRequestContext
            )
        );

        return GraphServiceClient::createWithAuthenticationProvider($authProvider);
    }
}
