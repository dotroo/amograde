<?php

namespace MVC\Models;

use MVC\Core\App;
use MVC\Core\Model;
use Db\Db;
use \League\OAuth2\Client\Token\AccessToken;

class TokensModel extends Model
{
    private $accessToken;
    private $refreshToken;
    private $expires;
    private $clientId;
    private $accountId;
    private $baseDomain;

    public function __construct(?array $params, string $baseDomain)
    {
        if ($params !== null){
            $this->accessToken = $params['access_token'];
            $this->refreshToken = $params['refresh_token'];
            $this->expires = $params['expires'];
            $this->baseDomain = $baseDomain;
        }
    }

    public function setClientId (string $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return array|bool
     */
    public function getData(int $accountId)
    {
        Db::getInstance();
        $selectOauth = "SELECT oauth.access_token, oauth.refresh_token, oauth.expires, accounts.base_domain FROM oauth JOIN accounts ON oauth.account_id = accounts.account_id WHERE oauth.account_id=?";
        $runSelectOauth = Db::request($selectOauth, [$accountId]);
        $resultSelectOauth = $runSelectOauth->fetch();
        return $resultSelectOauth;
    }

    /**
     * @return AccessToken|bool
     */
    public function getAccessTokenByAccountId (int $accountId)
    {
        $options = $this->getData($accountId);
        if ($options !== false) {
            return new AccessToken($options);
        } else {
            return false;
        }
    }

    public function write(): void
    {
        if (
            $this->accessToken !== null 
            && $this->refreshToken !== null 
            && $this->baseDomain !== null
        )  {
                Db::getInstance();
                //найдем accountId для поиска строки в таблице oauth
                $selectOauthAccountId = "SELECT oauth.account_id FROM oauth JOIN accounts ON oauth.account_id = accounts.account_id AND accounts.base_domain=?";
                $runSelectOauth = Db::request($selectOauthAccountId, [$this->baseDomain]);
                $resultSelectOauth = $runSelectOauth->fetch();
                App::$logger->getLogger('runtime')->log(var_dump($resultSelectOauth));
                if (is_array($resultSelectOauth)) {
                    $this->accountId = $resultSelectOauth['account_id'];
                }

                if ($this->accountId !== null) {
                    App::$logger->getLogger('runtime')->log('found record by account id: ' . (string)$this->accountId);
                    //обновим старые токены
                    $sqlData = [
                        'access_token' => $this->accessToken,
                        'refresh_token' => $this->refreshToken,
                        'expires' => $this->expires,
                        'account_id' => $this->accountId
                    ];
                    $updateTokens = "UPDATE oauth SET access_token = :access_token, refresh_token = :refresh_token, expires = :expires WHERE account_id = :account_id";
                    
                    Db::request($updateTokens, $sqlData);
                    App::$logger->getLogger('runtime')->log('updated record by account id: ' . (string)$this->accountId);

                } else {
                    App::$logger->getLogger('runtime')->log('no record by account id');
                    //найдем account_id в таблице accounts
                    $selectAccountsAccountId = "SELECT account_id FROM accounts WHERE base_domain=?";
                    $runSelectAccounts = Db::request($selectAccountsAccountId, [$this->baseDomain]);
                    $resultSelectAccounts = $runSelectAccounts->fetch();
                    if (is_array($resultSelectAccounts)) {
                        $this->accountId = $resultSelectAccounts['account_id'];
                    }

                    //добавим новые токены и аккаунт
                    $sqlData = [
                        'access_token' => $this->accessToken,
                        'refresh_token' => $this->refreshToken,
                        'expires' => $this->expires,
                        'client_id' => $this->clientId,
                        'account_id' => $this->accountId
                    ];
                    $insertTokens = "INSERT INTO oauth(access_token, refresh_token, expires, client_id, account_id) VALUES (:access_token, :refresh_token, :expires, :client_id, :account_id)";
                    Db::request($insertTokens, $sqlData);
                    App::$logger->getLogger('runtime')->log('added new record with account id: ' . (string)$this->accountId);
                }
            }
    }
}