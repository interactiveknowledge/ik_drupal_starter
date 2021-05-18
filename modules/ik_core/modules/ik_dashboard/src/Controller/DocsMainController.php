<?php

namespace Drupal\ik_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * A documentation controller.
 */
class DocsMainController extends ControllerBase {
  public function content() {
    $content = null;
    $config = \Drupal::config('system.site');
    $title = $config->get('name');
    $config = $this->config('ik_dashboard.docs.main');
    $settings = array_keys(array_filter($config->get()));

    $entityManager = \Drupal::service('entity_type.manager');
    $types = $entityManager->getDefinitions();

    $content .= '<ul>';

    foreach ($types as $type => $data) {
      $check = false;
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($type);

      if (in_array($type . '_all', $settings)) {
        $check = true;
      } else {
        foreach (array_keys($bundles) as $key) {
          if (!in_array($type . '_' . $key, $settings)) {
            unset($bundles[$key]);
          } else {
            $check = true;
          }
        }
      }

      if ($check) {
        $content .= '<li>' . $data->getLabel();

        if (count($bundles)) {
          $content .= '<ul>';
          foreach ($bundles as $bundle => $info) {
            $content .= '<li><a href="/docs/' . $type .'/' . $bundle . '">' . $info['label'] . '</a></li>';
          }
          $content .= '</ul>';
        }

        $content .= '</li>';
      }
      
    }
    $content .= '<ul>';

    $build = [
      '#title' => 'Documentation for ' . $title,
      '#markup' => $content,
    ];

    return $build;   
  }
}