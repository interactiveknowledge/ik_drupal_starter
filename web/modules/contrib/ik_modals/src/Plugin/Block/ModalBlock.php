<?php

namespace Drupal\ik_modals\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that will output modal entity content.
 *
 * @Block(
 *   id = "ik_modals_block",
 *   admin_label = @Translation("Modals Block"),
 * )
 */
class ModalBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage for our entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface;
   */
  protected $entityStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ModalBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entityQuery
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityStorage = $entityTypeManager->getStorage('modal');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = $this->entityTypeManager->getStorage('modal')->getQuery();
    $query->condition('status', 1);
    $query->sort('created', 'DESC');
    $ids = $query->execute();

    $modals = $this->entityTypeManager->getStorage('modal')->loadMultiple($ids);

    foreach ($modals as $key => $modal) {
      // Only show active modals.
      if ($modal->isActive() === FALSE) {
        unset($modals[$key]);
      }
    }
    $viewBuilder = $this->entityTypeManager->getViewBuilder('modal');

    // Add hook for overriding which view mode to show.
    return $viewBuilder->viewMultiple($modals);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view published modal entities');
  }

}
