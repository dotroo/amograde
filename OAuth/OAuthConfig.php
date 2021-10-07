<?php

namespace OAuth;

use \AmoCRM\OAuth\OAuthConfigInterface;

class OAuthConfig implements OAuthConfigInterface
{

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getIntegrationId(): string
    {
        return $this->config['INTEGRATION_ID'];
    }

    public function getSecretKey(): string
    {
        return $this->config['SECRET_KEY'];
    }

    public function getRedirectDomain(): string
    {
        return $this->config['REDIRECT_URI'];
    }
}