<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Hcaptcha
{
    private const HCAPTCHA_ENDPOINT = 'https://hcaptcha.com/siteverify';

    private HttpClientInterface $httpClient;
    private RequestStack $requestStack;
    private string $hcaptchaSecretKey;

    
    public function __construct(
        HttpClientInterface $httpClient,
        RequestStack $requestStack,
        string $hcaptchaSecretKey
    )
    {
        $this->httpClient = $httpClient;
        $this->requestStack = $requestStack;
        $this->hcaptchaSecretKey = $hcaptchaSecretKey;
    }

    public function isHcaptchaValid(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return false;
        }

        $options = [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'secret'   => $this->hcaptchaSecretKey,
                'response' => $request->request->get('h-captcha-response')
            ]
        ];

        $response = $this->httpClient->request('POST', self::HCAPTCHA_ENDPOINT, $options);

        $data = $response->toArray();

        return $data['success'];
    }
}
