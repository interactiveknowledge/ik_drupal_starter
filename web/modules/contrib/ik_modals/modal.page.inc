<?php

/**
 * @file
 * Contains modal.page.inc.
 *
 * Page callback for Modal entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Modal templates.
 *
 * Default template: modal.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_modal(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
