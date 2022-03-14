<?php

namespace Drupal\ik_modals;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ModalStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Modal revision IDs for a specific Modal.
   *
   * @param \Drupal\ik_modals\Entity\ModalInterface $entity
   *   The Modal entity.
   *
   * @return int[]
   *   Modal revision IDs (in ascending order).
   */
  public function revisionIds(ModalInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Modal author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Modal revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\ik_modals\Entity\ModalInterface $entity
   *   The Modal entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ModalInterface $entity);

  /**
   * Unsets the language for all Modal with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
