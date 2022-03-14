<?php

namespace Drupal\ik_modals;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\ik_modals\Entity\ModalInterface;

/**
 * Defines the storage handler class for Modal entities.
 *
 * This extends the base storage class, adding required special handling for
 * Modal entities.
 *
 * @ingroup ik_modals
 */
class ModalStorage extends SqlContentEntityStorage implements ModalStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ModalInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {modal_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {modal_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ModalInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {modal_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('modal_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
