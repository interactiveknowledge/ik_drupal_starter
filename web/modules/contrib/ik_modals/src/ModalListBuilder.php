<?php

namespace Drupal\ik_modals;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\ik_modals\Entity\ModalType;

/**
 * Defines a class to build a listing of Modal entities.
 *
 * @ingroup ik_modals
 */
class ModalListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Modal ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Modal Type');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ik_modals\Entity\Modal */

    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.modal.edit_form',
      ['modal' => $entity->id()]
    );
    $row['type'] = ModalType::load($entity->bundle())->label();
    $row['published'] = $entity->isActive() === TRUE ? 'Active' : 'Inactive';
    return $row + parent::buildRow($entity);
  }

}
