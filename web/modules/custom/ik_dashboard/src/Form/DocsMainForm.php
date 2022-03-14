<?php

namespace Drupal\ik_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class DocsMainForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ik_dashboard_main';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ik_dashboard.docs.main',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ik_dashboard.docs.main');
    $config = $config->get();

    $entityManager = \Drupal::service('entity_type.manager');
    $types = $entityManager->getDefinitions();

    $form['main'] = ['#markup' => 'Check off the items that should be ACTIVE in the documentation'];

    foreach ($types as $type => $data) {
      $form[$type] = [
        '#type' => 'fieldset',
        '#title' => $data->getLabel(),
      ];

      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($type);

      if (count($bundles)) {
        $form[$type][$type . '_all'] = [
          '#type' => 'checkbox',
          '#title' =>  'All',
          '#default_value' => isset($config[$type . '_all']) ? $config[$type . '_all'] : null
        ];

        foreach ($bundles as $bundle => $info) {
          $form[$type][$type . '_' . $bundle] = [
            '#type' => 'checkbox',
            '#title' =>  $info['label'],
            '#default_value' => isset($config[$type . '_all']) && $config[$type . '_all'] ? $config[$type . '_all'] : (isset($config[$type . '_' . $bundle]) ? $config[$type . '_' . $bundle] : null)
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      // Retrieve the configuration
    $config = $this->configFactory->getEditable('ik_dashboard.docs.main');
    $values = $form_state->getValues();

    foreach ($values as $field => $value) {
      $config->set($field, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }
}