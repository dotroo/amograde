<?php

namespace MVC\Controllers;

use Logger\Logger;
use MVC\Core\Controller;
use MVC\Models\TokensModel;
use \AmoCRM\Client\AmoCRMApiClient;
use \AmoCRM\Client\AmoCRMApiClientFactory;
use \AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Collections\ContactsCollection;
use \AmoCRM\Collections\Leads\LeadsCollection;
use \AmoCRM\Collections\Customers\CustomersCollection;
use \AmoCRM\Collections\LinksCollection;
use \AmoCRM\Models\CompanyModel;
use \AmoCRM\Models\ContactModel;
use \AmoCRM\Models\LeadModel;
use \AmoCRM\Models\Customers\CustomerModel;
use \League\OAuth2\Client\Token\AccessTokenInterface;

class EntitiesController extends Controller
{
    public function handle()
    {
        //hardcode
        $accountId = 28780795;
        $baseDomain = 'nikitav.amocrm.com';
        $entitiesCount = 10;

        $apiClient = $this->apiClientFactory->make();
        $tokensModel = new TokensModel (null, $baseDomain);
        $accessToken = $tokensModel->getAccessTokenByAccountId($accountId);
        if ($accessToken === false) {
            throw new \Exception("Access Token is empty");
        } else {
            $apiClient->setAccessToken($accessToken)
                ->setAccountBaseDomain($accessToken->getValues()['base_domain']);

            $leadSeed = [
                'price' => rand(0, 1000),
                'name' => '',
                'contact' => [
                    'name' => ''
                ],
                'company' => [
                    'name' => ''
                ]
            ];

            $leadData = [];

            for ($i = 0; $i < $entitiesCount; $i++) {
                $leadSeed['name'] = 'new Lead ' . $i;
                $leadSeed['contact']['name'] ='new Contact ' . $i;
                $leadSeed['company']['name'] = 'new Company ' . $i;

                $leadData[] = $leadSeed;
            }

            $leadsCollection = new LeadsCollection();
            $customersCollection = new CustomersCollection();
            $leadCounter = 0;
            
            foreach ($leadData as $extLead) {
                $lead = (new LeadModel())
                    ->setName($extLead['name'])
                    ->setPrice($extLead['price'])
                    ->setContacts(
                        (new ContactsCollection)
                            ->add(
                                (new ContactModel())
                                    ->setName($extLead['contact']['name'])
                            )
                    )
                    ->setCompany(
                        (new CompanyModel())
                            ->setName($extLead['company']['name'])
                    );
            
                $leadsCollection->add($lead);
                $customer = (new CustomerModel())
                    ->setName('new Customer ' . $leadCounter)
                    ->setNextDate(time()+86400);
                $customersCollection->add($customer);
                $leadCounter++;
            }   

            //создадим буферные коллекции для отправки менее 100 сущностей за запрос
            $bufferLeadsCollection = new LeadsCollection();
            $bufferCustomersCollection = new CustomersCollection();
            $customersCounter = 0;

            for ($i = 0; $i <= $leadsCollection->count(); $i++) {
                if ($bufferLeadsCollection->count() < 25 && $entitiesCount > 0) {
                    Logger::getLogger('runtime')->log("Adding lead {$i} to the buffer collection");
                    $bufferLeadsCollection->add($leadsCollection->offsetGet($i));
                    $bufferCustomersCollection->add($customersCollection->offsetGet($i));
                    $entitiesCount--;
                    Logger::getLogger('runtime')->log($entitiesCount . ' entities left');
                } else {
                    Logger::getLogger('runtime')->log('Start sending buffer collections');
                    //создадим сущности
                    try {
                        $addedLeadsCollection = $apiClient->leads()->addComplex($bufferLeadsCollection);
                        $addedCustomersCollection = $apiClient->customers()->add($bufferCustomersCollection);
                    } catch (AmoCRMApiException $e) {
                        Logger::getLogger('runtime')->log($e->getLastRequestInfo());
                        echo $e->getMessage();
                    }
                    //свяжем сущности
                    $linksCollection = new LinksCollection();
                    foreach ($addedLeadsCollection as $addedLead) {
                        Logger::getLogger('runtime')->log('Customers counter: ' . $customersCounter);
                        $linksCollection->add($addedLead->getContacts()->first());
                        $linksCollection->add($addedLead->getCompany());
                        $linkEntities = $apiClient->customers()->link($addedCustomersCollection->offsetGet($customersCounter), $linksCollection);
                        $linksCollection->clear();
                        $customersCounter++;
                        
                    }
                    //очистим буферные коллекции и добавим следующей элемент коллекции
                    $bufferLeadsCollection->clear();
                    $bufferCustomersCollection->clear();

                    Logger::getLogger('runtime')->log('Cleared buffer collections');

                    if ($leadsCollection->offsetExists($i)) {
                        Logger::getLogger('runtime')->log("Adding lead {$i} to the buffer collection");

                        $bufferLeadsCollection->add($leadsCollection->offsetGet($i));
                        $bufferCustomersCollection->add($customersCollection->offsetGet($i));
                        $entitiesCount--;
                        Logger::getLogger('runtime')->log($entitiesCount . ' entities left');
                    }   
                }
            }
        }  
    }
}