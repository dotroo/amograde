<?php

include_once __DIR__ . '/../vendor/autoload.php';

use MVC\Core\App;

App::init();
App::$kernel->launch();