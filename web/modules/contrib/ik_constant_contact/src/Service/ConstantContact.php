<?php

namespace Drupal\ik_constant_contact\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ConstantContact.
 *
 * Class to handle API calls to Constant Contact.
 */
class ConstantContact {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Cache\CacheBackendInterface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   *   Drupal cache.
   */
  protected $cache;

  /**
   * Drupal\Core\Config\ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   *   Drupal config.
   */
  protected $config;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   *   Drupal logging.
   */
  protected $loggerFactory;

  /**
   * Drupal\Core\Messenger\MessengerInterface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   *   Messenger Interface.
   */
  protected $messenger;

  /**
   * Drupal\Core\Site\Settings.
   *
   * @var \Drupal\Core\Site\Settings
   *   Drupal site settings.
   */
  protected $settings;

  /**
   * GuzzleHttp\Client.
   *
   * @var \GuzzleHttp\Client
   *   Guzzle HTTP Client.
   */
  protected $httpClient;

  /**
   * \Drupal\Core\Extension\ModuleHandlerInterface
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler interface
   */
  protected $moduleHandler;

  /**
   * The Constant Contact v3 API endpoint.
   *
   * @var string
   */
  protected $apiUrl = 'https://api.cc.email/v3';

  /**
   * The URL to use for authorization.
   *
   * @var string
   */
  protected $authUrl = 'https://authz.constantcontact.com/oauth2/default/v1/authorize';

  /**
   * The URL to use for token oauth.
   *
   * @var string
   */
  protected $tokenUrl = 'https://authz.constantcontact.com/oauth2/default/v1/token';

  /**
   * Constructs the class.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The interface for cache implementations.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The factory for logging channels.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The runtime messages sent out to individual users on the page.
   * @param \Drupal\Core\Site\Settings $settings
   *   The read settings that are initialized with the class.
   * @param \GuzzleHttp\Client $httpClient
   *   The client for sending HTTP requests.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(CacheBackendInterface $cache, ConfigFactory $config, LoggerChannelFactoryInterface $loggerFactory, MessengerInterface $messenger, Settings $settings, Client $httpClient, ModuleHandlerInterface $moduleHandler) {
    $this->cache = $cache;
    $this->config = $config;
    $this->loggerFactory = $loggerFactory;
    $this->messenger = $messenger;
    $this->settings = $settings;
    $this->httpClient = $httpClient;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Returns the configurations for the class.
   *
   * @return array
   *   Returns an array with all configuration settings.
   */
  public function getConfig() {
    // Get our settings from settings.php.
    $settings = $this->settings::get('ik_constant_contact');
    $clientId = isset($settings['client_id']) ? $settings['client_id'] : NULL;
    $secret = isset($settings['client_secret']) ? $settings['client_secret'] : NULL;
    $authType = isset($settings['auth_type']) ? $settings['auth_type'] : NULL;
    $configType = 'settings.php';

    // If nothing is in settings.php, let's check our config files.
    if (!$settings) {
      $clientId = $this->config->get('ik_constant_contact.config')->get('client_id');
      $secret = $this->config->get('ik_constant_contact.config')->get('client_secret');
      $authType = $this->config->get('ik_constant_contact.config')->get('auth_type');
      $configType = 'config';
    }

    return [
      'client_id' => $clientId,
      'client_secret' => $secret,
      'auth_type' => $authType,
      'config_type' => $configType, // Application client_id and other info found in settings.php or via config
      'access_token' => $this->config->get('ik_constant_contact.tokens')->get('access_token'),
      'refresh_token' => $this->config->get('ik_constant_contact.tokens')->get('refresh_token'),
      'authentication_url' => $this->authUrl,
      'token_url' => $this->tokenUrl,
      'contact_url' => $this->apiUrl . '/contacts',
      'contact_lists_url' => $this->apiUrl . '/contact_lists',
      'campaigns_url' => $this->apiUrl . '/emails',
      'campaign_activity_url' => $this->apiUrl . '/emails/activities',

      // Add fields to configuration for signup form block configuration
      // @see https://v3.developer.constantcontact.com/api_guide/contacts_create_or_update.html#method-request-body
      'fields' => [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'company_name' => 'Company',
        'job_title' => 'Job Title',
        'street_address' => 'Address',
        'phone_number' => 'Phone Number',
        'birthday' => 'Birthday',
        'anniversary' => 'Anniversary',
      ],
      'address_subfields' => [
        'street' => 'Street',
        'city' => 'City',
        'state' => 'State',
        'postal_code' => 'Postal Code',
        'country' => 'Country',
      ],
    ];
  }

  /**
   * Shared method to generate the rest of the request body.
   * 
   * @NOTE that email_address, permission_to_send are not added hear since the fields are
   * different per api call type. For example, the list_memberships, the email_address field. 
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/contacts_create_or_update.html#method-request-body
   *
   * @param array $data - posted data from our form
   * @param object $body - An object already generated.
   * @return object $body
   */
  protected function buildResponseBody(array $data, object $body) {
    $fields = $this->getConfig()['fields'];

    foreach ($fields as $field => $name) {
      if (isset($data[$field]) && $data[$field]) {
        if ($field === 'birthday') {
          if (isset($data[$field]['month']) && $data[$field]['month'] && isset($data[$field]['day']) && $data[$field]['day']) {
            $body->birthday_month = (int)$data[$field]['month'];
            $body->birthday_day = (int)$data[$field]['day'];
          }
        } else if ($field === 'street_address') {
          $body->{$field} = (object)$data[$field];
        } else {
          $body->{$field} = $data[$field];
        }
      }
    }

    if (isset($data['custom_fields']) && count($data['custom_fields']) > 0) {
      foreach ($data['custom_fields'] as $id => $value) {
        $body->custom_fields[] = ['custom_field_id' => $id, 'value' => $value];
      }
    }

    return $body;
  }

  /**
   * Creates a new contact by posting to Constant Contact API.
   *
   * @param array $data
   *   Array of data to send to Constant Contact.
   *    Requires 'email_address' key.
   *    Can also accept 'first_name' and 'last_name'.
   * @param array $listIDs
   *   An array of list UUIDs where we want to add this contact.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/createContact
   */
  private function createContact(array $data, $listIDs) {
    $config = $this->getConfig();

    $body = (object) [
      'email_address' => (object) [
        'address' => NULL,
        'permission_to_send' => NULL,
      ],
      'first_name' => NULL,
      'last_name' => NULL,
      'create_source' => NULL,
      'list_memberships' => NULL,
    ];

    $body = $this->buildResponseBody($data, $body);

    // Add our required fields.
    $body->email_address->address = $data['email_address'];
    $body->email_address->permission_to_send = 'implicit';
    $body->list_memberships = $listIDs;
    $body->create_source = 'Account';

    $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
    $this->moduleHandler->invokeAll('ik_constant_contact_contact_create_data_alter', [$data, &$body]);

    try {
      $response = $this->httpClient->request('POST', $config['contact_url'], [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
        'body' => json_encode($body),
      ]);

      $this->handleResponse($response, 'createContact');
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }


  /**
   * Fetch the details of a single campaign.
   *
   * @param string $id
   *   The id of the campaign.
   *
   * @return mixed
   *   An stdClass of the campaign.
   * @throws \GuzzleHttp\Exception\GuzzleException
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/email_campaign_id.html
   */
  public function getCampaign(string $id) {
    $config = $this->getConfig();
    try {
      $response = $this->httpClient->request('GET', $config['campaigns_url'] . '/' . $id, [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $json = json_decode($response->getBody()->getContents());
      return $json;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);
    }
  }

  /**
   * Get the campaign activity details by id.
   *
   * @param string $id
   *   The id of the activity.
   *
   * @return mixed
   *   A stdClass of the activity.
   * @throws \GuzzleHttp\Exception\GuzzleException
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/email_campaigns_activity_id.html
   */
  public function getCampaignActivity(string $id) {
    $config = $this->getConfig();
    $url = $config['campaign_activity_url'] . '/' . $id;
    $url .= '?include=permalink_url';
    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $json = json_decode($response->getBody()->getContents());
      return $json;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);
    }
  }

  /**
   * Returns a list of the campaigns.
   *
   * @param array $status
   *   An option to filter campaigns by status.
   *
   * @return array
   *   An array of campaigns.
   * @throws \GuzzleHttp\Exception\GuzzleException
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/email_campaigns_collection.html
   */
  public function getCampaigns($status = []) {
    $config = $this->getConfig();
    try {
      $response = $this->httpClient->request('GET', $config['campaigns_url'], [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $json = json_decode($response->getBody()->getContents());
      $list = [];

      foreach ($json->campaigns as $campaign) {
        if (!empty($status) && in_array($campaign->current_status, $status)) {
          $list[] = $campaign;
        }
        else if (empty($status)) {
          $list[] = $campaign;
        }

      }
      return $list;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);
    }
  }

  /**
   * Checks if a contact exists already.
   *
   * @param array $data
   *   Array of data to send. 'email_address' key is required.
   *
   * @return array
   *   Returns a json response that determines if a contact
   *   already exists or was deleted from the list.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/getContact
   */
  private function getContact(array $data) {
    $config = $this->getConfig();

    try {
      $response = $this->httpClient->request('GET', $config['contact_url'] . '?email=' . $data['email_address'], [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $json = json_decode($response->getBody()->getContents());

      if ($json->contacts) {
        return $json;
      }
      else {
        return $this->getDeleted($this->apiUrl . '/contacts?status=deleted&include_count=TRUE', $data['email_address']);
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }

  /**
   * Gets contact lists from Constant Contact API.
   *
   * @return array
   *   Returns an array of lists that the account has access to.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contact_Lists/getLists
   */
  public function getContactLists() {
    $config = $this->getConfig();
    $cid = 'ik_constant_contact.lists';
    $cache = $this->cache->get($cid);

    if ($cache && $cache->data && count($cache->data) > 0) {
      return $cache->data;
    }
    else {
      // Update access tokens.
      $this->refreshToken(false);

      if (isset($config['access_token'])) {
        try {
          $response = $this->httpClient->request('GET', $config['contact_lists_url'], [
            'headers' => [
              'Authorization' => 'Bearer ' . $config['access_token'],
              'cache-control' => 'no-cache',
              'content-type' => 'application/json',
              'accept' => 'application/json',
            ],
          ]);

          $json = json_decode($response->getBody()->getContents());
          $lists = [];

          if ($json->lists) {
            foreach ($json->lists as $list) {
              $lists[$list->list_id] = $list;
            }

            $this->saveContactLists($lists);
            return $lists;
          }
          else {
            $this->messenger->addMessage($this->t('There was a problem getting your available contact lists.'), 'error');
          }
        }
        catch (\Exception $e) {
          $this->loggerFactory->get('ik_constant_contact')->error($e);
          $this->messenger->addMessage($this->t('There was a problem getting your available contact lists.'), 'error');
        }
      }
      else {
        return [];
      }
    }
  }

  /**
   * Returns custom fields available
   *
   * @return mixed
   *   A stdClass of custom fields.
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/get_custom_fields.html
   */
  public function getCustomFields() {
    $config = $this->getConfig();
    $url = $this->apiUrl . '/contact_custom_fields';

    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $json = json_decode($response->getBody()->getContents());
      return $json;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);
    }
  }

  /**
   * Checks if a contact is deleted from a list.
   *
   * This loops through all the deleted contacts of a
   * list and returns if there is a match to the email address.
   *
   * @param string $endpoint
   *   The endpoint to check. @see $this->getContact()
   * @param string $email
   *   The email address we're looking for.
   *
   * @return array
   *   Returns an array of a matched deleted contact.
   *
   * @see https://community.constantcontact.com/t5/Developer-Support-ask-questions/API-v-3-409-conflict-on-POST-create-a-Contact-User-doesn-t/td-p/327518
   */
  private function getDeleted($endpoint, $email) {
    $config = $this->getConfig();

    $deleted = $this->httpClient->request('GET', $endpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $config['access_token'],
        'cache-control' => 'no-cache',
        'content-type' => 'application/json',
        'accept' => 'application/json',
      ],
    ]);

    $deleted = json_decode($deleted->getBody()->getContents());
    $match = NULL;

    if (count($deleted->contacts)) {
      foreach ($deleted->contacts as $value) {
        if ($value->email_address->address === $email) {
          $match = $value;
        }
      }
    }

    if (!$match &&  property_exists($deleted, '_links') && property_exists($deleted->_links, 'next') && property_exists($deleted->_links->next, 'href')) {
      $match = $this->getDeleted('https://api.cc.email' . $deleted->_links->next->href, $email);
    }

    return $match;
  }

  /**
   * Get the permanent link of a campaign.
   *
   * @param string $id
   *   The campaign id.
   *
   * @return null|string
   *   The URL of the campaign's permanent link.
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getPermaLinkFromCampaign(string $id) {
    $url = NULL;
    if (!$id) {
      return NULL;
    }
    $campaign = $this->getCampain($id);
    foreach ($campaign->campaign_activities as $activity) {
      if ($activity->role != 'permalink') {
        continue;
      }
      $act = $this->getCampaignActivity($activity->campaign_activity_id);
      if ($act) {
        return $act->permalink_url;
      }
    }
    return NULL;
  }

  /**
   * Handles API response for adding a contact.
   *
   * @param object $response
   *   The json_decoded json response.
   * @param string $method
   *   The name of the method that the response came from.
   *
   * @return array
   *   Returns an array that includes the method name and
   *   the statuscode except if it is coming from getContact method.
   *   Then it returns an array of the contact that matches.
   */
  private function handleResponse($response, $method) {
    if (($response->getStatusCode() === 200) || ($response->getStatusCode() === 201 && $method === 'createContact')) {
      $json = json_decode($response->getBody()->getContents());

      $this->loggerFactory->get('ik_constant_contact')->info('@method has been executed successfully.', ['@method' => $method]);

      if ($method === 'getContact') {
        return $json;
      }

      return [
        'method' => $method,
        'response' => $response->getStatusCode(),
      ];
    }
    else {
      $statuscode = $response->getStatusCode();
      $responsecode = $response->getReasonPhrase();

      $this->loggerFactory->get('ik_constant_contact')->error('Call to @method resulted in @status response. @responsecode', [
        '@method' => $method,
        '@status' => $statuscode,
        '@responsecode' => $responsecode,
      ]);

      return [
        'error' => 'There was a problem signing up. Please try again later.',
      ];
    }
  }

  /**
   * Submits a contact to the API. 
   * Used to be used on CostantContactBlockForm but using $this->submitContactForm instead.
   * Determine if contact needs to be updated or created.
   *
   * @param array $data
   *   Data to create/update a contact.
   *   Requires a 'email_address' key.
   *   But can also accept 'first_name' and 'last_name' key.
   * @param array $listIDs
   *   An array of list UUIDs to post the contact to.
   *
   * @return array
   *   Returns an error if there is an error.
   *   Otherwise it sends the info to other methods.
   *
   * @see $this->updateContact
   * @see $this->putContact
   * @see $this->createContact
   */
  public function postContact(array $data = [], $listIDs = []) {
    $config = $this->getConfig();
    $enabled = $this->config->get('ik_constant_contact.enabled_lists')->getRawData();

    if (!$config['client_id'] || !$config['client_secret'] || !$config['access_token'] || !$data) {
      $msg = 'Missing credentials for postContact';

      $this->loggerFactory->get('ik_constant_contact')->error($msg);

      return [
        'error' => $msg,
      ];
    }

    if (!$listIDs || count($listIDs) === 0) {
      $msg = 'A listID is required.';

      $this->loggerFactory->get('ik_constant_contact')->error($msg);

      return [
        'error' => $msg,
      ];
    }

    foreach ($listIDs as $listID) {
      if (!isset($enabled[$listID]) || $enabled[$listID] !== 1) {
        $msg = 'The listID provided does not exist or is not enabled.';
  
        $this->loggerFactory->get('ik_constant_contact')->error($msg);
  
        return [
          'error' => $msg,
        ];
      }
    }

    if (!isset($data['email_address'])) {
      $msg = 'An email address is required';

      $this->loggerFactory->get('ik_constant_contact')->error($msg);

      return [
        'error' => $msg,
      ];
    }

    // Refresh our tokens before every request.
    $this->refreshToken();

    // Check if contact already exists.
    $exists = (array) $this->getContact($data);

    // If yes, updateContact.
    // If no, createContact.
    // If previous deleted, putContact.
    if (isset($exists['contacts']) && count($exists['contacts']) > 0) {
      $this->updateContact($data, $exists['contacts'][0], $listIDs);
    }
    elseif ($exists && isset($exists['deleted_at'])) {
      $this->putContact($exists, $data, $listIDs);
    }
    else {
      $this->createContact($data, $listIDs);
    }
  }

  /**
   * Updates a contact if it already exists and has been deleted.
   *
   * @param array $contact
   *   The response from $this->getDeleted.
   * @param array $data
   *   The $data provided originally. @see $this->postContact.
   * @param array $listIDs
   *   The list IDs we want to add contact to.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/putContact
   * @see $this->getDeleted
   *
   * @TODO perhaps combine this with updateContact. The difference is that $contact is
   * an array here and an object in updateContact.
   */
  private function putContact(array $contact, array $data, $listIDs) {
    $config = $this->getConfig();

    $body = (object) $contact;

    $body = $this->buildResponseBody($data, $body);

    $body->email_address->permission_to_send = 'implicit';
    // To resubscribe a contact after an unsubscribe update_source must equal Contact. 
    // @see https://v3.developer.constantcontact.com/api_guide/contacts_re-subscribe.html#re-subscribing-contacts
    $body->update_source = 'Contact';
    $body->list_memberships = $listIDs;

    $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
    $this->moduleHandler->invokeAll('ik_constant_contact_contact_update_data_alter', [$data, &$body]);

    try {
      $response = $this->httpClient->request('PUT', $config['contact_url'] . '/' . $contact['contact_id'], [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
        'body' => json_encode($body),
      ]);

      $this->handleResponse($response, 'putContact');

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }

  /**
   * Makes authenticated request to Constant Contact to refresh tokens.
   *
   * @see https://v3.developer.constantcontact.com/api_guide/server_flow.html#refreshing-an-access-token
   */
  public function refreshToken($updateLists = true) {
    $config = $this->getConfig();
    if (!$config['client_id'] || !$config['client_secret'] || !$config['refresh_token']) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->request('POST', $this->tokenUrl, [
        'headers' => [
          'Authorization' => 'Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
        ],
        'form_params' => [
          'refresh_token' => $config['refresh_token'],
          'grant_type' => 'refresh_token',
          'scope' => 'contact_data+campaign_data+offline_access'
        ],
      ]);

      $json = json_decode($response->getBody()->getContents());

      $this->saveTokens($json);

      if ($updateLists === true) {
        $this->getContactLists();
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }

  /**
   * Saves available contact lists to a cache.
   *
   * @param array $data
   *   An array of lists and list UUIDs from $this->getContactLists.
   */
  private function saveContactLists(array $data) {
    $cid = 'ik_constant_contact.lists';
    $this->cache->set($cid, $data);
  }

  /**
   * Saves access and refresh tokens to our config database.
   *
   * @param object $data
   *   Data object of data to save the token.
   *
   * @see $this->refreshToken
   */
  private function saveTokens($data) {
    if ($data && property_exists($data, 'access_token') && property_exists($data, 'refresh_token')) {
      $tokens = $this->config->getEditable('ik_constant_contact.tokens');
      $tokens->clear('ik_constant_contact.tokens');
      $tokens->set('access_token', $data->access_token);
      $tokens->set('refresh_token', $data->refresh_token);
      $tokens->set('timestamp', strtotime('now'));
      $tokens->save();
    }
    else {
      $this->loggerFactory->get('ik_constant_contact')->error('There was an error saving tokens');
    }
  }

  /**
   * Submission of contact form.
   * Replaces $this->postContact as of v. 2.0.9.
   *
   * @param array $data
   *   Data to create/update a contact.
   *   Requires a 'email_address' key.
   *   But can also accept 'first_name' and 'last_name' key.
   * @param array $listIDs
   *   An array of list UUIDs to post the contact to.
   *
   * @return array
   *   Returns an error if there is an error.
   *   Otherwise it sends the info to other methods.
   *
   * @see https://v3.developer.constantcontact.com/api_guide/contacts_create_or_update.html
   */
  public function submitContactForm(array $data = [], $listIDs = []) {
    $config = $this->getConfig();
    $enabled = $this->config->get('ik_constant_contact.enabled_lists')->getRawData();

    if (!$config['client_id'] || !$config['client_secret'] || !$config['access_token'] || !$data) {
      $msg = 'Missing credentials for postContact';

      $this->loggerFactory->get('ik_constant_contact')->error($msg);

      return [
        'error' => $msg,
      ];
    }

    if (!$listIDs || count($listIDs) === 0) {
      $msg = 'A listID is required.';

      $this->loggerFactory->get('ik_constant_contact')->error($msg);

      return [
        'error' => $msg,
      ];
    }

    foreach ($listIDs as $listID) {
      if (!isset($enabled[$listID]) || $enabled[$listID] !== 1) {
        $msg = 'The listID provided does not exist or is not enabled.';
  
        $this->loggerFactory->get('ik_constant_contact')->error($msg);
  
        return [
          'error' => $msg,
        ];
      }
    }

    if (!isset($data['email_address'])) {
      $msg = 'An email address is required';

      $this->loggerFactory->get('ik_constant_contact')->error($msg);

      return [
        'error' => $msg,
      ];
    }

    // Refresh our tokens before every request.
    $this->refreshToken();

    $body = (object)[];

    $body = $this->buildResponseBody($data, $body);

    // Add required fields.
    $body->email_address = $data['email_address'];
    $body->list_memberships = $listIDs;

    $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
    $this->moduleHandler->invokeAll('ik_constant_contact_contact_form_submission_alter', [$data, &$body]);

    try {
      $response = $this->httpClient->request('POST', $config['contact_url'] . '/sign_up_form', [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
        'body' => json_encode($body),
      ]);

      $this->handleResponse($response, 'submitContactForm');

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ik_constant_contact')->error($e);
      
      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }

  /**
   * Updates a contact if it already exists on a list.
   *
   * @param array $data
   *   Provided data to update the contact with.
   * @param object $contact
   *   Contact match to provided data.
   * @param array $listIDs
   *   An array of list UUIDs that the contact is being updated on.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/putContact
   */
  private function updateContact(array $data, $contact, $listIDs) {
    $config = $this->getConfig();

    if ($contact && property_exists($contact, 'contact_id')) {
      
      $body = $contact;
      $body = $this->buildResponseBody($data, $body);

      // Add required fields
      $body->email_address->address = $data['email_address'];
      $body->email_address->permission_to_send = 'implicit';
      $body->update_source = 'Contact';
      $body->list_memberships = $listIDs;

      $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
      $this->moduleHandler->invokeAll('ik_constant_contact_contact_update_data_alter', [$data, &$body]);

      try {
        $response = $this->httpClient->request('PUT', $config['contact_url'] . '/' . $contact->contact_id, [
          'headers' => [
            'Authorization' => 'Bearer ' . $config['access_token'],
            'cache-control' => 'no-cache',
            'content-type' => 'application/json',
            'accept' => 'application/json',
          ],
          'body' => json_encode($body),
        ]);

        return $this->handleResponse($response, 'updateContact');

      }
      catch (\Exception $e) {
        $this->loggerFactory->get('ik_constant_contact')->error($e);
        
        // Return the error to show an error on form submission
        return ['error' => $e];
      }
    }
    else {
      $this->loggerFactory->get('ik_constant_contact')->error('error: No contact id provided for updateContact method');
      return ['error: No contact id provided'];
    }
  }

}
