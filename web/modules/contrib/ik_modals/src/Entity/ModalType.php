<?php

namespace Drupal\ik_modals\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Modal type entity.
 *
 * @ConfigEntityType(
 *   id = "modal_type",
 *   label = @Translation("Modal type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ik_modals\ModalTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ik_modals\Form\ModalTypeForm",
 *       "edit" = "Drupal\ik_modals\Form\ModalTypeForm",
 *       "delete" = "Drupal\ik_modals\Form\ModalTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ik_modals\ModalTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "modal_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "modal",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/modal_type/{modal_type}",
 *     "add-form" = "/admin/structure/modal_type/add",
 *     "edit-form" = "/admin/structure/modal_type/{modal_type}/edit",
 *     "delete-form" = "/admin/structure/modal_type/{modal_type}/delete",
 *     "collection" = "/admin/structure/modal_type"
 *   }
 * )
 */
class ModalType extends ConfigEntityBundleBase implements ModalTypeInterface {

  /**
   * The Modal type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Modal type label.
   *
   * @var string
   */
  protected $label;

}
