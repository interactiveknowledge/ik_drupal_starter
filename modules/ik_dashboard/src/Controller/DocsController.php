<?php

namespace Drupal\ik_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;


/**
 * A documentation controller.
 */
class DocsController extends ControllerBase {

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

  public function content($type = null, $bundle = null, Request $request) {
    $entityManager = \Drupal::service('entity_type.manager');
    $entityType = $entityManager->getDefinition($type);
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($type);

    $title = isset($bundles[$bundle]) ? $bundles[$bundle]['label'] : null;
    $fields = $this->fields($type, $bundle);
    $config = \Drupal::config('ik_dashboard.docs.' . $type . '.' . $bundle);
    $hiddenFields = $config->get('hidden_fields') ? $config->get('hidden_fields') : [];
    
    $content = null;

    $user = \Drupal::currentUser();
    $roles = $user->getRoles();
    $path = \Drupal::request()->getRequestUri();

    if (in_array('interactive_knowledge', $roles)) {
      $content .= '<a href="' . $path . '/edit" class="button">Edit</a>';
    }

    $content .= $config->get('general');

    foreach ($fields as $machine => $info) {
      $description = $info['description'] ? $info['description'] : 'No documentation has been added for this field yet.';

      if (!in_array($machine, $hiddenFields)) {
        $content .= '<h2>' . $info['label'] . '</h2>';
        $content .= $config->get($machine) ? $config->get($machine) : $description;

        if (isset($info['paragraph']) && count($info['paragraph']) > 0) {
          $content .= '<p>Also see documentation for paragraphs:</p>';
          $content .= '<ul>';
          foreach ($info['paragraph'] as $paragraph) {
            // Need own controller for Paragraphs
            $content .= '<li><a href="/docs/paragraph/' . $paragraph . '">' . $this->title('paragraph', $paragraph, $request) . '</a></li>';
          }
          $content .= '</ul>';
        }
      }
    }

    $build = [
      '#title' => 'Documentation for ' . $title,
      '#markup' => $content,
    ];

    return $build;
  }

  /**
   *  A placeholder method for the tabs. 
   * @see hook_menu_local_tasks_alter for additional info
   * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Menu!menu.api.php/function/hook_menu_local_tasks_alter/8
   */
  public function tab() {
    return '';
  }

  public function title($type, $bundle, Request $request) {
    $entityManager = \Drupal::service('entity_type.manager');
    $entityType = $entityManager->getDefinition($type);
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($type);

    $title = isset($bundles[$bundle]) ? $bundles[$bundle]['label'] : null;

    return 'Documentation for ' . $title;
  }
}