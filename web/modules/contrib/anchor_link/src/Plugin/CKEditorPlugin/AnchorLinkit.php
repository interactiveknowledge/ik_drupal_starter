<?php

namespace Drupal\anchor_link\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "link" plugin.
 *
 * @CKEditorPlugin(
 *   id = "anchorlinkit",
 *   label = @Translation("Anchor Link to LinkIt Bridge")
 * )
 */
class AnchorLinkit extends PluginBase implements CKEditorPluginContextualInterface, ContainerFactoryPluginInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    if ($this->moduleHandler->moduleExists('linkit')) {
      $settings = $editor->getSettings();
      if (!empty($settings['plugins']['drupallink']['linkit_enabled']) && $settings['plugins']['drupallink']['linkit_enabled']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['link'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $url = Url::fromRoute('linkit.autocomplete', ['linkit_profile_id' => $editor->getSettings()['plugins']['drupallink']['linkit_profile']]);
    return [
      'drupalLink_dialogLinkitPath' => $url->toString(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'linkit/linkit.autocomplete',
      'anchor_link/autocomplete',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'anchor_link') . '/js/anchorlinkit.js';
  }

}
