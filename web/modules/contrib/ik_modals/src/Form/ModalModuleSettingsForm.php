<?php

namespace Drupal\ik_modals\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ConstantContactConfig.
 *
 * Configuration form for adjusting content for the social feeds block.
 */
class ModalModuleSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   *   Config Factory Interface.
   */
  protected $configFactory;

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
   * ConstantContactConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Drupal\Core\Config\ConfigFactoryInterface.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal\Core\Messenger\MessengerInterface.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Symfony\Component\HttpFoundation\RequestStack.
   */
  public function __construct(ConfigFactoryInterface $configFactory, MessengerInterface $messenger, RequestStack $requestStack) {
    parent::__construct($configFactory);
    $this->messenger = $messenger;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ik_modals_settingsuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ik_modals.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ik_modals.settings');
    $ipdataApi = $config->get('ipdata_api_key') ? $config->get('ipdata_api_key') : $config->get('api_key');
    $abstractApi = $config->get('abstract_api_key');
    $service = $config->get('geolocation_service') ? $config->get('geolocation_service') : null;
    $geolocate = $config->get('geolocate');
    $bootstrapCss = $config->get('bootstrap_css');
    $bootstrapJs = $config->get('bootstrap_js');
    $debug = $config->get('debug_mode');

    $form['debug'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debug Mode'),
    ];

    $form['debug']['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Turn on debug mode.'),
      '#description' => $this->t('Check to see output in the browser console why or why not modals are showing. Note this has security implications and should not be on in production environments.'),
      '#default_value' => $debug ? $debug : NULL,
    ];

    $form['bootstrap'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bootstrap Library Settings'),
    ];

    $form['bootstrap']['bootstrap_js'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove Bootstrap JS'),
      '#description' => $this->t('Check if your theme already uses bootstrap javascript files.'),
      '#default_value' => $bootstrapJs ? $bootstrapJs : NULL,
    ];

    $form['bootstrap']['bootstrap_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove Bootstrap CSS'),
      '#description' => $this->t('Check if your theme already uses bootstrap or if the css causes conflict and you want to write your own.'),
      '#default_value' => $bootstrapCss ? $bootstrapCss : NULL,
    ];

    $form['geolocation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geolocation Settings'),
    ];

    $form['geolocation']['geolocate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate geolocation'),
      '#description' => $this->t('Fill in additional information by selecting the geolocation service you would like to use.'),
      '#default_value' => $geolocate ? $geolocate : NULL,
    ];

    $form['geolocation']['services'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geolocation Services'),
      '#states' => [
        'visible' => [
          ':input[name="geolocate"]' => [
            'checked' => true
          ]
        ]
      ]
    ];

    $form['geolocation']['services']['geolocation_service'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Geolocation Service'),
      '#options' => [
        'geoip2' => $this->t('GeoIP2 PHP Library'),
        'ipdata' => $this->t('IPData'),
        'apstractapi' => $this->t('AbstractAPI Geolocation API'),
      ],
      '#default_value' => $service ? $service : 'geoip2',
    ];

    $form['geolocation']['services']['ipdata'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('ipdata'),
      '#states' => [
        'visible' => [
          ':input[name="geolocation_service"]' => [
            'value' => 'ipdata'
          ]
        ]
      ]
    ];

    $form['geolocation']['services']['ipdata']['ipdata_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IPData API Key'),
      '#description' => $this->t('Get an API key from <a href="https://ipdata.co/" target="_blank" rel="nofollow noreferrer">ipdata.co</a> and enter it here.'),
      '#default_value' => $ipdataApi ? $ipdataApi : NULL,
    ];

    $form['geolocation']['services']['apstractapi'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AbstractAPI'),
      '#states' => [
        'visible' => [
          ':input[name="geolocation_service"]' => [
            'value' => 'apstractapi'
          ]
        ]
      ]
    ];

    $form['geolocation']['services']['apstractapi']['abstract_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AbstractAPI IP Geolocation Key'),
      '#description' => $this->t('Get an API key from <a href="https://abstractapi.com/" target="_blank" rel="nofollow noreferrer">abstractapi.com</a> and enter it here.'),
      '#default_value' => $abstractApi ? $abstractApi : NULL,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ik_modals.settings');
    $ipdataApi = $form_state->getValue('ipdata_api_key');
    $abstractApi = $form_state->getValue('abstract_api_key');
    $geolocate = $form_state->getValue('geolocate');
    $service = $form_state->getValue('geolocation_service');
    $bootstrapCss = $form_state->getValue('bootstrap_css');
    $bootstrapJs = $form_state->getValue('bootstrap_js');
    $debug = $form_state->getValue('debug_mode');

    $config->clear('ik_modals.settings');

    $config->set('ipdata_api_key', $ipdataApi);
    $config->set('abstract_api_key', $abstractApi);
    $config->set('geolocate', $geolocate);
    $config->set('geolocation_service', $service);
    $config->set('bootstrap_css', $bootstrapCss);
    $config->set('bootstrap_js', $bootstrapJs);
    $config->set('debug_mode', $debug);
    $config->save();

    $this->messenger->addMessage($this->t('Your configuration has been saved'));
  }

}
