<?php

namespace MVC\Controllers;

use MVC\Core\Controller;
use MVC\Models\TokensModel;
use MVC\Models\AccountsModel;
use \AmoCRM\Client\AmoCRMApiClient;
use \AmoCRM\Client\AmoCRMApiClientFactory;
use \AmoCRM\Models\AccountModel;
use \AmoCRM\Exceptions\AmoCRMApiException;

class WebhookController extends Controller
{
    public function handle()
    {
        $apiClient = $this->apiClientFactory->make();
        $apiClient->setAccountBaseDomain($_GET['referer']);
        $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

        $apiClient->setAccessToken($accessToken);

        try {
            //получим данные аккаунта
            $account = $apiClient->account()->getCurrent();
            //и добавим их в базу
            $accountsModel = new AccountsModel($account->toArray());
            $accountsModel->write();
        } catch (AmoCRMApiException $e) {
            App::$logger->getLogger('runtime')->log(printError($e));
        }

        $tokenModel = new TokensModel($accessToken->jsonSerialize(), $_GET['referer']);
        App::$logger->getLogger('runtime')->log("built TokensModel on WebhookController");
        $tokenModel->setClientId($_GET['client_id']);
        App::$logger->getLogger('runtime')->log('Start writing tokens to DB');
        $tokenModel->write();
    }
}