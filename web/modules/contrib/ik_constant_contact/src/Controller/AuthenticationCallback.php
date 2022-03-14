<?php

namespace Drupal\ik_constant_contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\ik_constant_contact\Service\ConstantContact;

/**
 * Constant Contact Callback Controller.
 *
 * @package Drupal\ik_constant_contact\Controller
 */
class AuthenticationCallback extends ControllerBase {
  /**
   * Config factory service.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Symfony\Component\HttpFoundation\RequestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Messenger\MessengerInterface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   *   Messenger Interface.
   */
  protected $messenger;

  /**
   * GuzzleHttp\Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Constructor function.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger interface.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request Stack.
   * @param GuzzleHttp\Client $client
   *   Guzzle HTTP client.
   * @param \Drupal\ik_constant_contact\Service\ConstantContact $constantContact
   *   Constant contact service.
   */
  public function __construct(ConfigFactory $config, MessengerInterface $messenger, RequestStack $request_stack, Client $client, ConstantContact $constantContact) {
    $this->config = $config;
    $this->messenger = $messenger;
    $this->client = $client;
    $this->requestStack = $request_stack;
    $this->constantContact = $constantContact;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('request_stack'),
      $container->get('http_client'),
      $container->get('ik_constant_contact')
    );
  }

  /**
   * Callback URL handling for Constant Contact API.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return array
   *   Return markup for the page.
   */
  public function callbackUrl(Request $request) {
    $code = $request->get('code');
    $error = $request->get('error');
    $errorDescription = $request->get('error_description');

    $settings = $this->constantContact->getConfig();
    $client_id = isset($settings['client_id']) ? $settings['client_id'] : NULL;
    $tokenUrl = isset($settings['token_url']) ? $settings['token_url'] : NULL;
    $client_secret = isset($settings['client_secret']) ? $settings['client_secret'] : NULL;

    if ($client_id && $client_secret && $code) {
      try {
        $client = $this->client;
        $response = $client->request('POST', $tokenUrl, [
          'headers' => [
            'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
          ],
          'form_params' => [
            'code' => $code,
            'redirect_uri' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/admin/config/services/ik-constant-contact/callback',
            'grant_type' => 'authorization_code',
            'scope' => 'contact_data+campaign_data+offline_access'
          ],
        ]);

        $json = json_decode($response->getBody()->getContents());

        if ($json && property_exists($json, 'access_token') && property_exists($json, 'refresh_token')) {
          $tokens = $this->config->getEditable('ik_constant_contact.tokens');
          $tokens->clear('ik_constant_contact.tokens');
          $tokens->set('access_token', $json->access_token);
          $tokens->set('refresh_token', $json->refresh_token);
          $tokens->set('timestamp', strtotime('now'));
          $tokens->save();

          $this->messenger->addMessage($this->t('Tokens were successfully saved.'));
        }
        else {
          
          $this->messenger->addMessage($this->t('There was a problem authorizing your account.'), $this->messenger::TYPE_ERROR);
        }
      }
      catch (RequestException $e) {
        watchdog_exception('ik_constant_contact', $e);
        $this->messenger->addMessage($this->t('There was a problem authorizing your account.'), $this->messenger::TYPE_ERROR);
      }
    }
    else {
      $message = 'There was a problem authorizing your account. <br/>';

      if ($error) {
        $message .= 'Error: ' . $error . '<br/>';
      }

      if ($errorDescription) {
        $message .= '  Description: ' . $errorDescription;
      }

      $this->messenger->addMessage($this->t($message), $this->messenger::TYPE_ERROR);
    }

    return new RedirectResponse(Url::fromRoute('ik_constant_contact.config')->toString());
  }

}
