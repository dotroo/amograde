<?php

namespace MVC\Core;

use \AmoCRM\Client\AmoCRMApiClientFactory;

abstract class Controller
{
    // public $view;
    // public $model;
    /**
     *  @var AmoCRMApiClientFactory
     */
    protected AmoCRMApiClientFactory $apiClientFactory; 

    public function __construct(AmoCRMApiClientFactory $apiClientFactory)
    {
        if (!is_null($apiClientFactory)){
            $this->apiClientFactory = $apiClientFactory;
        }
    }

    public abstract function handle();
}