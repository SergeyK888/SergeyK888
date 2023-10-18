<?php

namespace core\user\model;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TagModel;
use AmoCRM\Models\Unsorted\FormsMetadata;
use AmoCRM\OAuth2\Client\Provider\AmoCRM;
use core\base\controller\BaseController;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Grant\AuthorizationCode;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Token\AccessTokenInterface;


class APIModel extends BaseController
{
    public $provider;

    public function __construct()
    {

        $this->provider = new AmoCRM([
            'clientId' => '6a6c9caf-1c70-4dbe-8667-7b63b4aab020',
            'clientSecret' => '4pWO1XmLeJuDQVBMjKvqIIkgii4Je9gwrkzVHwnXODWrtU6ETJjRgF8zpnaPtfKl',
            'redirectUri' => 'http://test.work/',
        ]);

        if (isset($_GET['referer'])) {
            $this->provider->setBaseDomain($_GET['referer']);
        }

        if (!file_exists(TOKEN_FILE)) {
            if (!isset($_GET['code'])) {
                $_SESSION['oauth2state'] = bin2hex(random_bytes(16));
                $authorizationUrl = $this->provider->getAuthorizationUrl(['state' => $_SESSION['oauth2state']]);
                header('Location: ' . $authorizationUrl);
            } elseif (empty($_GET['state']) || empty($_SESSION['oauth2state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
                exit('Invalid state');
            }

            /**
             * Ловим обратный код
             */
            try {
                $accessToken = $this->provider->getAccessToken(new AuthorizationCode(), [
                    'code' => $_GET['code'],
                ]);

                if (!$accessToken->hasExpired()) {
                    $this->saveToken([
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $this->provider->getBaseDomain(),
                    ]);
                }
            } catch (Exception $e) {
                die((string)$e);
            }

            $this->initAmoCRM = true;
            $ownerDetails = $this->provider->getResourceOwner($accessToken);

            header('Location: /');
        } else {
            $accessToken = $this->getToken();

            $this->provider->setBaseDomain($accessToken->getValues()['baseDomain']);

            if ($accessToken->hasExpired()) {
                /**
                 * Получаем токен по рефрешу
                 */
                try {
                    $accessToken = $this->provider->getAccessToken(new RefreshToken(), [
                        'refresh_token' => $accessToken->getRefreshToken(),
                    ]);

                    $this->saveToken([
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $this->provider->getBaseDomain(),
                    ]);

                } catch (Exception $e) {
                    die((string)$e);
                }
            }

            $token = $accessToken->getToken();

            try {
                $data = $this->provider->getHttpClient()
                    ->request('GET', $this->provider->urlAccount() . 'api/v2/account', [
                        'headers' => $this->provider->getHeaders($accessToken)
                    ]);

                $parsedBody = json_decode($data->getBody()->getContents(), true);

            } catch (GuzzleException $e) {
                var_dump((string)$e);
            }
        }
    }

    protected function saveToken($accessToken)
    {
        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            $data = [
                'accessToken' => $accessToken['accessToken'],
                'expires' => $accessToken['expires'],
                'refreshToken' => $accessToken['refreshToken'],
                'baseDomain' => $accessToken['baseDomain'],
            ];

            file_put_contents(TOKEN_FILE, json_encode($data));
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    protected function getToken()
    {
        $accessToken = json_decode(file_get_contents(TOKEN_FILE), true);

        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            return new \League\OAuth2\Client\Token\AccessToken([
                'access_token' => $accessToken['accessToken'],
                'refresh_token' => $accessToken['refreshToken'],
                'expires' => $accessToken['expires'],
                'baseDomain' => $accessToken['baseDomain'],
            ]);
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    public function sendLeads($data) {
        $accessToken = $this->getToken();

        $apiClient = new AmoCRMApiClient('6a6c9caf-1c70-4dbe-8667-7b63b4aab020', '4pWO1XmLeJuDQVBMjKvqIIkgii4Je9gwrkzVHwnXODWrtU6ETJjRgF8zpnaPtfKl', 'http://test.work/');

        $apiClient->setAccessToken($accessToken)
            ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
            ->onAccessTokenRefresh(
                function (AccessTokenInterface $accessToken, string $baseDomain) {
                    saveToken(
                        [
                            'accessToken' => $accessToken->getToken(),
                            'refreshToken' => $accessToken->getRefreshToken(),
                            'expires' => $accessToken->getExpires(),
                            'baseDomain' => $baseDomain,
                        ]
                    );
                }
            );

        //Представим, что у нас есть данные, полученные из сторонней системы
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $price = $data['price'];

        $externalData = [
            [
                'is_new' => false,
                'price' => $price,
                'name' => 'Lead 2 name',
                'contact' => [
                    'first_name' => $name,
                    'mail' => $email,
                    'phone' => $phone,
                ],
            ],
        ];

        $leadsCollection = new LeadsCollection();

        //Создадим модели и заполним ими коллекцию
        foreach ($externalData as $externalLead) {
            $lead = (new LeadModel())
                ->setName($externalLead['name'])
                ->setPrice($externalLead['price'])
                ->setContacts((new ContactsCollection())->add((new ContactModel())
                                ->setFirstName($externalLead['contact']['first_name'])
                                ->setCustomFieldsValues(
                                    (new CustomFieldsValuesCollection())
                                        ->add(
                                            (new MultitextCustomFieldValuesModel())
                                                ->setFieldCode('PHONE')
                                                ->setValues(
                                                    (new MultitextCustomFieldValueCollection())
                                                        ->add(
                                                            (new MultitextCustomFieldValueModel())
                                                                ->setValue($externalLead['contact']['phone'])
                                                        )
                                                )
                                        )
                                )
                                ->setCustomFieldsValues(
                                    (new CustomFieldsValuesCollection())
                                        ->add(
                                            (new MultitextCustomFieldValuesModel())
                                                ->setFieldCode('EMAIL')
                                                ->setValues(
                                                    (new MultitextCustomFieldValueCollection())
                                                        ->add(
                                                            (new MultitextCustomFieldValueModel())
                                                                ->setValue($externalLead['contact']['mail'])
                                                        )
                                                )
                                        )
                                )
                        )
                );

            if ($externalLead['is_new']) {
                $lead->setMetadata(
                    (new FormsMetadata())
                        ->setFormId('my_best_form')
                        ->setFormName('Обратная связь')
                        ->setFormPage('https://example.com/form')
                        ->setFormSentAt(mktime(date('h'), date('i'), date('s'), date('m'), date('d'), date('Y')))
                        ->setReferer('https://google.com/search')
                        ->setIp('192.168.0.1')
                );
            }

            $leadsCollection->add($lead);
        }

        //Создадим сделки
        try {
            $addedLeadsCollection = $apiClient->leads()->addComplex($leadsCollection);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        header("Location: /");
    }
}