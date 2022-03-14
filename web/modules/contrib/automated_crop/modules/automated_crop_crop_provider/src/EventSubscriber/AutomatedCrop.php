<?php

namespace Drupal\automated_crop_crop_provider\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\crop\Events\AutomaticCrop;
use Drupal\crop\Events\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber running automated crop after a crop is needed.
 */
class AutomatedCrop implements EventSubscriberInterface {

  /**
   * Crop entity storage.
   *
   * @var \Drupal\crop\CropStorageInterface
   */
  protected $cropStorage;

  /**
   * Constructs an AutomatedCrop object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->cropStorage = $entity_type_manager->getStorage('crop');
  }

  /**
   * Run the generation of crop.
   *
   * @param \Drupal\crop\Events\AutomaticCrop $event
   *   The Event to process.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generateAutomatedCrop(AutomaticCrop $event) {
    if (!$this->applies($event->getConfiguration()['automatic_crop_provider'])) {
      return;
    }

    /** @var \Drupal\crop\Entity\CropType $crop_type */
    $crop_type = $event->getCropType();
    /** @var \Drupal\Core\Image\Image $image */
    $image = $event->getImage();
    $hard_limit = $crop_type->getHardLimit();
    $soft_limit = $crop_type->getSoftLimit();
    $configuration = [
      'image'        => $image,
      'min_width'    => isset($hard_limit['width']) ? $hard_limit['width'] : $soft_limit['width'],
      'min_height'   => isset($hard_limit['height']) ? $hard_limit['height'] : $soft_limit['height'],
      'aspect_ratio' => !empty($crop_type->getAspectRatio()) ? $crop_type->getAspectRatio() : 'NaN',
    ];

    /** @var \Drupal\automated_crop\AutomatedCropInterface $automated_crop */
    $automated_crop = \Drupal::service('plugin.manager.automated_crop')->createInstance($event->getConfiguration()['automatic_crop_provider'], $configuration);

    $values = [
      'type' => $crop_type->id(),
      'uri' => $image->getSource(),
      'x' => $automated_crop->anchor()['x'] + ($automated_crop->size()['width'] / 2),
      'y' => $automated_crop->anchor()['y'] + ($automated_crop->size()['height'] / 2),
      'width' => $automated_crop->size()['width'],
      'height' => $automated_crop->size()['height'],
    ];

    /** @var \Drupal\crop\CropInterface $crop */
    $crop = $this->cropStorage->create($values);
    $crop->save();

    $event->setCrop($crop);
  }

  /**
   * Determines if the subscriber applies to a specific conditions.
   *
   * @param string $provider_name
   *   The machine name of automatic crop provider.
   *
   * @return bool
   *   True if this subscriber can generate crop.
   */
  private function applies($provider_name) {
    return in_array($provider_name, array_keys(\Drupal::service('plugin.manager.automated_crop')->getProviderOptionsList()));
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    return [Events::AUTOMATIC_CROP => [['generateAutomatedCrop', 100]]];
  }

}
