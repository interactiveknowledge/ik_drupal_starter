<?php

namespace Drupal\ik_modals\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Modal entities.
 *
 * @ingroup ik_modals
 */
interface ModalInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Modal active status.
   *
   * @return bool
   *   Active status of the modal
   */
  public function isActive();

  /**
   * Gets the show delay value.
   *
   * @return int
   *   Number of seconds before modal is displayed to user.
   */
  public function getShowDelay();

  /**
   * Gets the show repeat value.
   *
   * @return int
   *   Number of days before a modal is re-shown to a user.
   */
  public function getShowRepeat();

  /**
   * Gets the show convert value.
   *
   * @return int
   *   Number of days before a modal is re-shown to
   *   a user only if the user clicks inside the modal.
   */
  public function getShowConvert();

  /**
   * Gets the show visit value.
   *
   * @return int
   *   Number of days since users' last visit, then the modal will show.
   */
  public function getShowVisit();

  /**
   * Gets the show dates value.
   *
   * @return array
   *   Array of start and end dates when the modal is supposed to be shown.
   */
  public function getShowDates();

  /**
   * Gets the url pages.
   *
   * @return array
   *   Array of paths to show the modal when user visits.
   */
  public function getUrlPages();

  /**
   * Gets the url referrers.
   *
   * @return array
   *   Array of paths to show the modal when user is
   *   referred from these pages to the current one.
   */
  public function getUrlReferrers();

  /**
   * Gets the user country restrictions.
   *
   * @return array
   *   Array of country codes that a user must be
   *   in to show modal.
   *   (Requires geolocation to be activated)
   */
  public function getUserCountries();

  /**
   * Gets the user US state restrictions.
   *
   * @return array
   *   Array of state abbreviations that a
   *   user must be in to show modal.
   *   (Requires geolocation to be activated)
   */
  public function getUserStates();

  /**
   * Gets the Modal name.
   *
   * @return string
   *   Name of the Modal.
   */
  public function getTitle();

  /**
   * Sets the Modal name.
   *
   * @param string $name
   *   The Modal name.
   *
   * @return \Drupal\ik_modals\Entity\ModalInterface
   *   The called Modal entity.
   */
  public function setTitle($name);

  /**
   * Gets the Modal creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Modal.
   */
  public function getCreatedTime();

  /**
   * Sets the Modal creation timestamp.
   *
   * @param int $timestamp
   *   The Modal creation timestamp.
   *
   * @return \Drupal\ik_modals\Entity\ModalInterface
   *   The called Modal entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Modal published status indicator.
   *
   * Unpublished Modal are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Modal is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Modal.
   *
   * @param bool $published
   *   TRUE to set this Modal to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ik_modals\Entity\ModalInterface
   *   The called Modal entity.
   */
  public function setPublished($published);

  /**
   * Gets the Modal revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Modal revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\ik_modals\Entity\ModalInterface
   *   The called Modal entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Modal revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Modal revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\ik_modals\Entity\ModalInterface
   *   The called Modal entity.
   */
  public function setRevisionUserId($uid);

}
