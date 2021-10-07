<?php

namespace MVC\Core;

class Router
{
    public static function resolve()
    {
        $routes = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        //игнорируем домен в routes[0], оставляем только составляющие uri
        $result[0] = $routes[1];
        $result[1] = $routes[2];
        
        return $result;
    }
}