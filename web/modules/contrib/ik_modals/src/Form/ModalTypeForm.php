<?php

namespace Drupal\ik_modals\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ModalTypeForm.
 */
class ModalTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $modal_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $modal_type->label(),
      '#description' => $this->t("Label for the Modal type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $modal_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ik_modals\Entity\ModalType::load',
      ],
      '#disabled' => !$modal_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $modal_type = $this->entity;
    $status = $modal_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Modal type.', [
          '%label' => $modal_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Modal type.', [
          '%label' => $modal_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($modal_type->toUrl('collection'));
  }

}
