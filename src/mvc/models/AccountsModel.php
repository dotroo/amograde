<?php

namespace MVC\Models;

use MVC\Core\App;
use MVC\Core\Model;
use Db\Db;

class AccountsModel extends Model
{

    private $accountId;
    private $baseDomain;

    public function __construct(array $amoCRMAccountModel)
    {
        $this->accountId = $amoCRMAccountModel['id'];
        $this->baseDomain = $amoCRMAccountModel['subdomain'] . '.amocrm.com';
    }

    public function getData(int $index)
    {
        //TODO
    }

    public function write()
    {
        if ($this->accountId !== null && $this->baseDomain !== null) {
            Db::getInstance();
            //попытаемся добавить строку, если такая уже есть - поймаем исключение
            try
            {
                $insertAccount = "INSERT INTO accounts(account_id, base_domain) VALUES (:account_id, :base_domain)";
                $sqlData = [
                    'account_id' => $this->accountId,
                    'base_domain' => $this->baseDomain
                ];
                Db::request($insertAccount, $sqlData);
            }
            catch (\PDOException $e)
            {
                App::$logger->getLogger('runtime')->log('Caught exception on ' . __FILE__ . ': ' . $e->getMessage() . "\nContinue execution");
            }
        }
    }
}