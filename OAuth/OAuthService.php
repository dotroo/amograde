<?php

namespace OAuth;

use \AmoCRM\OAuth\OAuthServiceInterface;
use MVC\Models\TokensModel;
use \League\OAuth2\Client\Token\AccessTokenInterface;

class OAuthService implements OAuthServiceInterface
{
    public function saveOAuthToken(AccessTokenInterface $accessToken, string $baseDomain): void
    {
        $tokenModel = new TokensModel($accessToken->jsonSerialize(), $baseDomain);
        $tokenModel->write();
    }
}