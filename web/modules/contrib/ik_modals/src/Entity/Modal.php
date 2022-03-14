<?php

namespace Drupal\ik_modals\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Modal entity.
 *
 * @ingroup ik_modals
 *
 * @ContentEntityType(
 *   id = "modal",
 *   label = @Translation("Modal"),
 *   bundle_label = @Translation("Modal type"),
 *   handlers = {
 *     "storage" = "Drupal\ik_modals\ModalStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ik_modals\ModalListBuilder",
 *     "views_data" = "Drupal\ik_modals\Entity\ModalViewsData",
 *     "translation" = "Drupal\ik_modals\ModalTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\ik_modals\Form\ModalForm",
 *       "add" = "Drupal\ik_modals\Form\ModalForm",
 *       "edit" = "Drupal\ik_modals\Form\ModalForm",
 *       "delete" = "Drupal\ik_modals\Form\ModalDeleteForm",
 *     },
 *     "access" = "Drupal\ik_modals\ModalAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\ik_modals\ModalHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "modal",
 *   data_table = "modal_field_data",
 *   revision_table = "modal_revision",
 *   revision_data_table = "modal_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer modal entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
*   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/modal/{modal}",
 *     "add-page" = "/admin/content/modal/add",
 *     "add-form" = "/admin/content/modal/add/{modal_type}",
 *     "edit-form" = "/admin/content/modal/{modal}/edit",
 *     "delete-form" = "/admin/content/modal/{modal}/delete",
 *     "version-history" = "/admin/content/modal/{modal}/revisions",
 *     "revision" = "/admin/content/modal/{modal}/revisions/{modal_revision}/view",
 *     "revision_revert" = "/admin/content/modal/{modal}/revisions/{modal_revision}/revert",
 *     "revision_delete" = "/admin/content/modal/{modal}/revisions/{modal_revision}/delete",
 *     "translation_revert" = "/admin/content/modal/{modal}/revisions/{modal_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/modal",
 *   },
 *   bundle_entity_type = "modal_type",
 *   field_ui_base_route = "entity.modal_type.edit_form"
 * )
 */
class Modal extends RevisionableContentEntityBase implements ModalInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the modal owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    $active = FALSE;
    $now = strtotime('now');
    $dates = $this->getShowDates();

    if ($this->isPublished() === TRUE) {
      $active = TRUE;
    }

    if (!is_null($dates['start'])) {
      if ($dates['start'] >= $now || $dates['end'] < $now) {
        $active = FALSE;
      }
    }

    return $active;
  }

  /**
   * {@inheritdoc}
   */
  public function getShowDelay() {
    return $this->get('show_delay')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getShowRepeat() {
    return $this->get('show_repeat')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getShowConvert() {
    return $this->get('show_convert')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getShowVisit() {
    return $this->get('show_convert')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getShowDates() {
    $start = $this->get('show_dates')->value . ' 00:00:00';
    $end = ($this->get('show_dates')->end_value ? $this->get('show_dates')->end_value : $this->get('show_dates')->value) . ' 23:59:59';

    return [
      'start' => strtotime($start),
      'end' => strtotime($end),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlPages() {
    return $this->explodeUrls('url_pages');
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlReferrers() {
    return $this->explodeUrls('url_referrer');
  }

  /**
   * {@inheritdoc}
   */
  public function getUserCountries() {
    $output = [];
    $value = $this->get('user_country')->getValue();

    foreach ($value as $v) {
      $output[] = $v['value'];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserStates() {
    $output = [];
    $value = $this->get('user_states')->getValue();

    foreach ($value as $v) {
      $output[] = $v['value'];
    }

    return $output;
  }

  /**
   * Helper method to check url format.
   *
   * @param string $field_name
   *   Field name of the field value to get and process.
   *
   * @return array
   *   Array of url paths that are formatted.
   */
  protected function explodeUrls($field_name) {
    $output = [];

    $value = $this->get($field_name)->value;
    $value = nl2br($value);
    $value = explode('<br />', $value);

    if (is_array($value)) {
      foreach ($value as $v) {
        if (!preg_match('/http(s?)\:\/\//i', $v) && strpos($v, '/') === FALSE && $v !== '') {
          $output[] = '/' . trim($v);
        }
        else {
          $output[] = trim($v);
        }
      }
    }
    elseif ($value !== '') {
      if (!preg_match('/http(s?)\:\/\//i', $v) && strpos($v, '/') === FALSE && $v !== '') {
        $output = ['/' . trim($value)];
      }
      else {
        $output = [trim($value)];
      }
    }

    return array_filter($output);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $countries = \Drupal::service('address.country_repository')->getList();
    $states = \Drupal::service('address.subdivision_repository')->getList(['US']);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Modal entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('An administrative title for the Modal entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['show_delay'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Display delay'))
      ->setDescription(t('Number of seconds it takes to show the modal after page load. Default value is 3.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(3)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_repeat'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Repeat showing the modal every __ days'))
      ->setDescription(t('If blank, it will not show again.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_convert'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('If the user interacts, show the modal again in __ days'))
      ->setDescription(t('If the user does not click a link inside the modal, if left blank, or if the user dismisses the modal, it will not show again.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_visit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Only show if user has not visited the website in __ days'))
      ->setDescription(t('If blank, it will show immediately'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_dates'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Only show during this date range'))
      ->setDescription(t('If blank, it will show immediately.'))
      ->setRevisionable(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['url_pages'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Display only on pages'))
      ->setDescription(t('Add the urls to display the modal one per line. This field does accept a wildcard character (*).<br/>If left blank, modal will appear on the first page the user lands on where all other criteria pass.<br/>Use <code><front></code> or <code>/home</code> for the homepage.<br/>For internal paths, use a slash (/) prefix. (Ex: <code>/about</code>)'))
      ->setDisplayOptions('form', [
        'type'   => 'text_textarea',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['url_referrer'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Display on pages that are referred from these URLs'))
      ->setDescription(t('Add the referring urls one per line. This field does except a wildcard character (*).<br/>Use <code><front></code> or <code>/home</code> for the homepage.<br/>For internal paths, use a slash (/) prefix. (Ex: <code>/about</code>)'))
      ->setDisplayOptions('form', [
        'type'   => 'text_textarea',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['user_country'] = BaseFieldDefinition::create('list_string')
      ->setSetting('allowed_values', $countries)
      ->setLabel('Allow only users from these countries to see the modal')
      ->setRequired(FALSE)
      ->setCardinality(-1)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_states'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Allow only users from these US states to see the modal')
      ->setSetting('allowed_values', $states)
      ->setRequired(FALSE)
      ->setCardinality(-1)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
