<?php

namespace Drupal\automated_crop_crop_provider\EventSubscriber;

use Drupal\crop\Events\AutomaticCropProviders;
use Drupal\crop\Events\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe each automated crop providers as automatic crop api provider.
 */
class AutomatedCropProvider implements EventSubscriberInterface {

  /**
   * Register automated crop plugins as crop api providers.
   *
   * @param \Drupal\crop\Events\AutomaticCropProviders $event
   *   The Event to process.
   */
  public function addProvider(AutomaticCropProviders $event) {
    foreach (\Drupal::service('plugin.manager.automated_crop')->getProviderOptionsList() as $key => $provider) {
      $event->registerProvider([$key => $provider->render()]);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    return [Events::AUTOMATIC_CROP_PROVIDERS => [['addProvider', 100]]];
  }

}
