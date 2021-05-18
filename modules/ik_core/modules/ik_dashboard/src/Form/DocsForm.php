<?php

namespace Drupal\ik_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Configure example settings for this site.
 */
class DocsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ik_dashboard_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ik_dashboard.docs',
    ];
  }


  private function fields($type, $bundle) {
    $entityManager = \Drupal::service('entity.manager');
    $fields = []; 
  
    if (!empty($type) && !empty($bundle)) {
      foreach ($entityManager->getFieldDefinitions($type, $bundle) as $name => $definition) {
        if (!empty($definition->getTargetBundle())) {
          $fields[$name]['type'] = $definition->getType();
          $fields[$name]['label'] = $definition->getLabel();
          $fields[$name]['description'] = $definition->getDescription();

          // If Paragraphing. 
          if ($definition->getSetting('handler') === 'default:paragraph') {
            $settings = $definition->getSetting('handler_settings');
            $fields[$name]['paragraph'] = $settings['target_bundles'];
          }

          // Lists.
          if ($definition->getSetting('allowed_values')) {
            $fields[$name]['allowed_values'] = $definition->getSetting('allowed_values');
          }
        }
      }
    }

    return $fields;   
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $path = \Drupal::request()->getpathInfo();
    $args = explode('/',$path);
    $type = $args[2];
    $bundle = $args[3];
    $config = $this->config('ik_dashboard.docs.' . $type . '.' . $bundle);
    $baseConfig = $type . '.' . $bundle;
    $fields = $this->fields($type, $bundle);
    $hiddenFields = $config->get('hidden_fields');

    $form['config_base'] = [
      '#type' => 'hidden',
      '#value' => $baseConfig
    ];
    $form['config_type'] = [
      '#type' => 'hidden',
      '#value' => $args[2]
    ];
    $form['config_bundle'] = [
      '#type' => 'hidden',
      '#value' => $args[3]
    ];

    $form['general'] = array(
      '#type' => 'text_format',
      '#title' => 'General Info',
      '#format' => 'rich_text',
      '#default_value' => $config->get('general') ? $config->get('general') : null,
    );  

    foreach ($fields as $field => $info) {
      $form[$field . '_hide'] = array(
        '#type' => 'checkbox',
        '#title' => 'Hide documentation for ' . $info['label'] . '?',
        '#default_value' => $hiddenFields ? in_array($field, $hiddenFields) : null,
      );

      $form[$field] = array(
        '#type' => 'text_format',
        '#title' => $info['label'],
        '#format' => 'rich_text',
        '#default_value' => $config->get($field) ? $config->get($field) : $info['description'],
      );  
    }

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      // Retrieve the configuration
    $type = $form_state->getValue('config_type');
    $bundle = $form_state->getValue('config_bundle');
    $fields = $this->fields($type, $bundle);
    $config = $this->configFactory->getEditable('ik_dashboard.docs.' . $type . '.' . $bundle);
    $hidden = [];

    $general = $form_state->getValue('general');

    $config->set('general', isset($general['value']) ? $general['value'] : null);
  
    foreach ($fields as $field => $info) {
      if ($form_state->getValue($field)) {
        $value = $form_state->getValue($field);
        $config->set($field, $value['value']);
      }

      if ($form_state->getValue($field . '_hide')) {
        $hidden[] = $field;
      }
    }
    $config->set('hidden_fields', $hidden);

    $config->save();

    parent::submitForm($form, $form_state);
  }
}