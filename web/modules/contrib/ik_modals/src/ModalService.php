<?php

namespace Drupal\ik_modals;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ModalService.
 *
 * Class to handle modal management and send settings to drupalSettings.
 */
class ModalService {

  /**
   * Drupal\Core\Session\AccountInterface definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GuzzleHttp\Client.
   *
   * @var \GuzzleHttp\Client
   *   Guzzle HTTP Client.
   */
  protected $httpClient;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   *   Drupal logging.
   */
  protected $logger;

  /**
   * Symfony\Component\HttpFoundation\RequestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   *   The request object.
   */
  protected $requestStack;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   *   Temporary store user geoloation data.
   */
  protected $tempStore;

  /**
   * Constructs a new ModalService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account interface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The interface for cache implementations.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \GuzzleHttp\Client $httpClient
   *   The client for sending HTTP requests.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The factory for logging channels.
   * @param Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request object.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStore
   *   The factory for storing temporary private user data.
   */
  public function __construct(AccountInterface $account, CacheBackendInterface $cache, ConfigFactory $configFactory, DateFormatter $dateFormatter, EntityTypeManagerInterface $entityTypeManager, Client $httpClient, LoggerChannelFactoryInterface $loggerFactory, RequestStack $requestStack, PrivateTempStoreFactory $tempStore) {
    $this->account = $account;
    $this->cache = $cache;
    $this->config = $configFactory->get('ik_modals.settings');
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
    $this->httpClient = $httpClient;
    $this->logger = $loggerFactory;
    $this->requestStack = $requestStack;
    $this->tempStore = $tempStore->get('ik_modals');
  }

  /**
   * Returns status of geolocation configuration.
   *
   * @return bool
   *   Returns an boolean whether the geolocation setting is active.
   */
  public function isGeolocationActive() {
    $ipdataApi = $abstractApi = null;
    $geolocationService = $this->config->get('geolocation_service');
    $geoip2 = $geolocationService === 'geoip2';

    if ($geolocationService === 'ipdata') {
      $ipdataApi = $this->config->get('ipdata_api_key') ?  $this->config->get('ipdata_api_key') :  $this->config->get('api_key');
    }

    else if ($geolocationService === 'apstractapi') {
      $abstractApi = $this->config->get('abstract_api_key');
    }

    $geolocate = $this->config->get('geolocate');
    $geoip2 = (boolean) $this->config->get('geoip2');

    return (($ipdataApi || $abstractApi || $geoip2) && $geolocate);
  }

  /**
   * Returns the geolocation data for current user.
   *
   * @return array
   *   Returns an array of geolocation data based on user session.
   */
  protected function getGeolocationData() {
    $ipdataApi = $abstractApi = null;

    $geolocationService = $this->config->get('geolocation_service');
    $geoip2 = $geolocationService === 'geoip2';

    if ($geolocationService === 'ipdata') {
      $ipdataApi = $this->config->get('ipdata_api_key') ?  $this->config->get('ipdata_api_key') :  $this->config->get('api_key');
    }

    else if ($geolocationService === 'apstractapi') {
      $abstractApi = $this->config->get('abstract_api_key');
    }

    $returnData['geolocate'] = $this->isGeolocationActive();
    $returnData['geolocation_service'] = $geolocationService;
  
    // ipdada api @see https://docs.ipdata.co/
    // AbstractAPI @see https://app.abstractapi.com/api/ip-geolocation/documentation
    if ($ipdataApi || $abstractApi) {
      $userInfo = $this->tempStore->get('user_geolocation');

      if ($userInfo) {
        return (array) json_decode($userInfo);
      }
      else {
        if ($ipdataApi) {
          $response = $this->httpClient->request('GET', 'https://api.ipdata.co/?api-key=' . $ipdataApi);
        } else if ($abstractApi) {
          $response = $this->httpClient->request('GET', 'https://ipgeolocation.abstractapi.com/v1/?api_key=' . $abstractApi);
        }
        

        if ($response->getStatusCode() === 200) {
          $this->tempStore->set('user_geolocation', $response->getBody()->getContents());
          return (array) json_decode($response->getBody()->getContents());
        }
        else {
          $statuscode = $response->getStatusCode();
          $responsecode = $response->getReasonPhrase();

          $apiUsed = ($ipdataApi ? 'ipdata API' : 'AbstractAPI');

          $this->logger->get('ik_modals')->error($apiUsed . ' request resulted in @status response. @responsecode', [
            '@status' => $statuscode,
            '@responsecode' => $responsecode,
          ]);
        }
      }
    }

    // GeoIP2
    elseif ($geoip2 === true) {
      $record = NULL;
      $reader = new Reader(drupal_get_path('module', 'ik_modals') . '/includes/db/GeoLite2-City.mmdb');

      $ip = $this->requestStack->getCurrentRequest()->getClientIp();

      if (strpos($ip, ',') !== FALSE) {
        $ip = substr($ip, 0, strpos($ip, ','));
      }

      try {
        $record = $reader->city($ip);
      }
      catch (AddressNotFoundException $e) {
        $this->logger->get('ik_modals')->error('GeoIP2 could not find an match for IP address: @ip. @error', [
          '@ip' => $ip,
          '@error' => $e->getMessage(),
        ]);

        $record = NULL;
      }

      if ($record) {
        return [
          'country_code' => $record->country->isoCode,
          'region_code' => $record->subdivisions[0]->isoCode,
        ];
      }
    }

    // No service or not active.
    else {
      return NULL;
    }
  }

  /**
   * Returns the settings for all modal entities.
   *
   * @return array
   *   Returns an array of settings for modals and the site.
   */
  public function loadAllSettings() {
    $geolocate = $this->config->get('geolocate');
    $modals = $this->entityTypeManager->getStorage('modal')->loadMultiple();

    $settings['admin'] = (boolean) $this->config->get('admin');
    $settings['user'] = $geolocate === 1 ? $this->getGeolocationData() : NULL;
    $settings['debug'] = $this->config->get('debug_mode') === 1;
    $settings['geolocate'] = $this->config->get('geolocate') == 1 ? $this->config->get('geolocation_service') : false;

    foreach ($modals as $entity) {
      $settings['modal--' . $entity->id()] = $this->loadSettings($entity->id());
    }

    return $settings;
  }

  /**
   * Returns the settings for a modal entity.
   *
   * @return array
   *   Returns an array with all settings for modal entity.
   */
  public function loadSettings(int $id) {
    $entity = $this->entityTypeManager->getStorage('modal')->load($id);

    return [
      'active' => $entity->isActive(),
      'debugName' => $entity->getTitle(),
      'showAgainDismiss' => $entity->getShowRepeat(),
      'showAgainConvert' => $entity->getShowConvert(),
      'showAgainVisit' => $entity->getShowVisit(),
      'showDateStart' => $this->dateFormatter->format($entity->getShowDates()['start'], 'custom', 'Y-m-d'),
      'showDateEnd' => $this->dateFormatter->format($entity->getShowDates()['end'], 'custom', 'Y-m-d'),
      'showDelay' => $entity->getShowDelay(),
      'showIfReferred'  => $entity->getUrlReferrers(),
      'showLocationsCountries' => $entity->getUserCountries(),
      'showLocationsState' => $entity->getUserStates(),
      'showOnPages' => $entity->getUrlPages(),
      'userVisitedLast' => NULL,
    ];
  }

}
