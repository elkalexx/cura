<?php

namespace App\Services\MagentoService\Api;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MagentoTokenService
{
    private string $magentoUrl;

    private string $userName;

    private string $password;

    private const TOKEN_URL = '/V1/tfa/provider/google/authenticate';

    public function __construct()
    {
        $this->magentoUrl = Config::string('services.magento.apiRequestUrl');
        $this->userName = Config::string('services.magento.apiUsername');
        $this->password = Config::string('services.magento.apiPassword');
    }

    public function getToken(): string
    {
        $tokenExpiresAt = Setting::where('path', '=', 'customers.magento_api.access_token_expires_at')->first();

        if ($tokenExpiresAt) {
            $expiredAt = CarbonImmutable::create($tokenExpiresAt->value);

            if (! $expiredAt) {
                throw new Exception('Could not get expired at token from Magento');
            }

            if (! $expiredAt->isPast()) {
                $token = Setting::where('path', '=', 'customers.magento_api.access_token')->firstOrFail();
                if (! $token->secret_value) {
                    throw new Exception('Could not get token from Magento');
                }

                return $token->secret_value;
            }
        }

        $result = Http::post($this->magentoUrl.self::TOKEN_URL,
            [
                'username' => $this->userName,
                'password' => $this->password,
                'otp' => '072559',
            ]);

        if (! $result->successful()) {
            Log::error('Something went wrong getToken '.json_encode($result->body()));
            throw new Exception('Could not get token from Magento');
        }

        $body = json_decode($result->body());
        $expires_at = CarbonImmutable::now()->addMinutes(50)->toDateTimeString();

        Setting::updateOrCreate(
            ['path' => 'customers.magento_api.access_token'],
            ['secret_value' => $body]
        );

        Setting::updateOrCreate(
            ['path' => 'customers.magento_api.access_token_expires_at'],
            ['value' => $expires_at]
        );

        return $body;
    }
}
