<?php

namespace MVC\Core;

use MVC\Core\Router;
use MVC\Core\Kernel;
use OAuth\OAuthConfig;
use OAuth\OAuthService;
use Logger\Logger;

class App
{
    public static $router;
    public static $kernel;
    public static $apiClientFactory;
    public static $logger;

    public function init()
    {
        self::$router = new Router;
        self::$kernel = new Kernel;

        $appConfig = parse_ini_file(__DIR__ . '/../../../configs/app_config.ini');

        $oAuthConfig = new OAuthConfig($appConfig);
        $oAuthService = new OAuthService();
        //var_export($oAuthConfig); die;
        self::$apiClientFactory = new \AmoCRM\Client\AmoCRMApiClientFactory($oAuthConfig, $oAuthService);

        Logger::$PATH = __DIR__ . '/../../../logs';
        self::$logger = Logger::getLogger('runtime');
        
    }
}