<?php

namespace MVC\Core;

use MVC\Core\App;
use MVC\Controllers\WebhookController;

class Kernel
{
    private $defaultControllerName = 'HomeController';
    private $defaultActionName = 'index';
    
    public function launch()
    {
        list($controllerName, $actionName) = App::$router->resolve();
        $this->launchAction($controllerName, $actionName);
    }

    public function launchAction($controllerName, $actionName)
    {
        $controllerName = empty($controllerName) ? $this->defaultControllerName : ucfirst($controllerName) . 'Controller';
        if (!file_exists(__DIR__ . "/../controllers/{$controllerName}.php")) {
            App::$logger->getLogger('runtime')->log('No such controller:' . $controllerName);
        }

        $actionName = empty($actionName) ? $this->defaultActionName : $actionName;
        
        $controllerName = '\MVC\Controllers\\' . $controllerName;
        if (!method_exists($controllerName, $actionName)){
            App::$logger->getLogger('runtime')->log('No such method: ' . $actionName);
        }

        $controller = new $controllerName(App::$apiClientFactory);

        return $controller->$actionName();
    }
}