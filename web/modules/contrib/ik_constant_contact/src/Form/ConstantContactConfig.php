<?php

namespace Drupal\ik_constant_contact\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\ik_constant_contact\Service\ConstantContact;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ConstantContactConfig.
 *
 * Configuration form for adjusting content for the social feeds block.
 */
class ConstantContactConfig extends ConfigFormBase {

  /**
   * Drupal\Core\Messenger\MessengerInterface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   *   Messenger Interface.
   */
  protected $messenger;

  /**
   * Symfony\Component\HttpFoundation\RequestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\ik_constant_contact\Service\ConstantContact.
   *
   * @var \Drupal\ik_constant_contact\Service\ConstantContact
   *   Constant contact service.
   */
  protected $constantContact;

  /**
   * ConstantContactConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal\Core\Config\ConfigFactoryInterface.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal\Core\Messenger\MessengerInterface.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Symfony\Component\HttpFoundation\RequestStack.
   * @param \Drupal\ik_constant_contact\Service\ConstantContact $constantContact
   *   Constant contact service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, RequestStack $request_stack, ConstantContact $constantContact) {
    parent::__construct($config_factory);
    $this->messenger = $messenger;
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
      $container->get('ik_constant_contact')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ik_constant_contact_configure';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ik_constant_contact.config',
      'ik_constant_contact.tokens',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->constantContact->getConfig();
    $clientId = isset($settings['client_id']) ? $settings['client_id'] : NULL;
    $authType = isset($settings['auth_type']) ? $settings['auth_type'] : NULL;
    $configType = isset($settings['config_type']) ? $settings['config_type'] : 'config';
    $secret = isset($settings['client_secret']) ? $settings['client_secret'] : NULL;
    $tokens = $this->config('ik_constant_contact.tokens');
    $accessToken = isset($settings['access_token']) ? $settings['access_token'] : NULL;
    $refreshToken = isset($settings['refresh_token']) ? $settings['refresh_token'] : NULL;
    $authUrl = isset($settings['authentication_url']) ? $settings['authentication_url'] : NULL;

    $form['auth'] = [
      '#type' => 'details',
      '#title' => $this->t('Authorization Settings'),
      '#collapsible' => TRUE,
      '#open' => (!$clientId || !$secret || !$authType),
    ];

    $form['auth']['message'] = [
      '#markup' => $configType === 'settings.php' ? '<p>' . $this->t('<strong>NOTE:</strong> Application settings were found in your <strong>settings.php</strong> file. Please update information there or remove to use this form.') . '</p>' : '',
    ];

    $form['auth']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $clientId ? $clientId : NULL,
      '#required' => TRUE,
      '#disabled' => $configType === 'settings.php' ? TRUE : FALSE
    ];

    $form['auth']['auth_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Application OAuth2 Settings'),
      '#options' => [
        'auth_code' => $this->t('Authorization Code Flow'),
        // 'pcke' => $this->t('Proof Key for Code Exchange (PKCE) Flow (preferred)'),
      ],
      '#default_value' => $authType ? $authType : 'auth_code',
      '#required' => TRUE,
      '#disabled' => $configType === 'settings.php' ? TRUE : FALSE,
      '#description' => $this->t('Select your applications authentication settings. See <a href="https://v3.developer.constantcontact.com/api_guide/auth_overview.html#select-the-oauth2-flow-to-use" target="_blank" rel="nofollow noreferrer">OAuth2 Flow Types</a> for more details.')
    ];

    $form['auth']['client_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Secret'),
      '#default_value' => $secret ? $secret : NULL,
      '#required' => TRUE,
      '#disabled' => $configType === 'settings.php' ? TRUE : FALSE
    ];

    if ($accessToken && $refreshToken) {
      $form['tokens'] = [
        '#markup' => '<p>' . $this->t('Your account is successfully set up. Thank you! Last token was generated at @date', ['@date' => date('m/d/Y h:i a', $tokens->get('timestamp'))]) . '</p>',
      ];
    }
   
    // Show Authentication Button.
    // @see https://v3.developer.constantcontact.com/api_guide/server_flow.html#step-1-create-an-authorization-request
    if ($clientId && $secret && (!$accessToken || !$refreshToken) && !isset($_GET['code'])) {
      $buttonLabel = $this->t('Authorize Your Account');
      $additionalMarkup = '<p>' . $this->t('You will need to make sure the following URL is listed in your Constant Contact App before proceeding:') . '<br/><strong>' . $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/admin/config/services/ik-constant-contact/callback</strong></p>';
    } else {
      $buttonLabel = $this->t('Refresh Tokens Manually');
      $additionalMarkup = '';
    }
      
    $queryParams = [
      'client_id' => $clientId,
      'redirect_uri' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/admin/config/services/ik-constant-contact/callback',
      'response_type' => 'code',
      'state' => \Drupal::config('system.site')->get('uuid'),
      'scope' => 'offline_access'
    ];

    $authenticationUrl = Url::fromUri($authUrl, [
      'query' => $queryParams,
      'attributes' => [
        'class' => 'button'
      ]
    ]);

    $authenticationLink = Link::fromTextAndUrl(
      $buttonLabel,
      $authenticationUrl
    );

    // Weird bug that CC doesn't like encoded "+" sign
    $link = $authenticationLink->toString();
    $link = str_replace('scope=offline_access', 'scope=offline_access+contact_data+campaign_data', $link);

    $form['auth_code'] = [
      '#markup' => $additionalMarkup . '<p>' . $link . '</p>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ik_constant_contact.config');
    $tokens = $this->config('ik_constant_contact.tokens');
    $clientId = $form_state->getValue('client_id');
    $secret = $form_state->getValue('client_secret');
    $authType = $form_state->getValue('auth_type');
    $config->clear('ik_constant_contact.config');
    $config->set('client_id', $clientId);
    $config->set('client_secret', $secret);
    $config->set('auth_type', $authType);
    $config->save();
    $tokens->delete();

    $this->messenger->addMessage($this->t('Your configuration has been saved'));
  }

}
