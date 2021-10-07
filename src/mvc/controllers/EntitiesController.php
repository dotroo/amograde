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
                $leadSeed['name'] = 'Lead ' . $i;
                $leadSeed['contact']['name'] ='Contact ' . $i;
                $leadSeed['company']['name'] = 'Company ' . $i;

                $leadData[] = $leadSeed;
            }

            $leadsCollection = new LeadsCollection();
            $customersCollection = new CustomersCollection();

            $leadCounter = 0;
            
            foreach($leadData as $extLead) {
                //отправляем меньше 100 сущностей в одном запросе
                if ($leadsCollection->count() < 25) {
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
                        ->setName('Customer ' . $leadCounter)
                        ->setNextDate(time()+86400);

                    $customersCollection->add($customer);
                } else {
                    //создадим сущности
                    try {
                        $addedLeadsCollection = $apiClient->leads()->addComplex($leadsCollection);
                        $addedCustomersCollection = $apiClient->customers()->add($customersCollection);
                    } catch (AmoCRMApiException $e) {
                        Logger::getLogger('runtime')->log($e->getLastRequestInfo());
                        echo $e->getMessage();
                    }

                    //свяжем сущности
                    $linksCollection = new LinksCollection();

                    $customersCounter = 0;
                    foreach ($addedLeadsCollection as $addedLead) {
                        $linksCollection->add($addedLead->getContacts()->first());
                        $linksCollection->add($addedLead->getCompany());
                        $linkEntities = $apiClient->customers()->link($addedCustomersCollection->offsetGet($customersCounter), $linksCollection);
                        $linksCollection->clear();
                        $customersCounter++;
                    }
                    //очистим коллекцию, чтобы добавить следующую пачку
                    $leadsCollection->clear();
                }

                $leadCounter++;
            }
        }  
    }
}