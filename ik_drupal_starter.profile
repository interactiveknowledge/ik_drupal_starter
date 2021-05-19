<?php
/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

/**
 * Implements hook_install_tasks().
 */
function ik_drupal_starter_install_tasks() {
  $tasks = [];

  $tasks['ik_drupal_starter_set_default_theme'] = [];

  return $tasks;
}

/**
 * Sets the default and administration themes.
 */
function lightning_set_default_theme() {
  Drupal::configFactory()
    ->getEditable('system.theme')
    ->set('default', 'ik_client')
    ->set('admin', 'adminimal_theme')
    ->save(TRUE);

  // Use the admin theme for creating content.
  if (Drupal::moduleHandler()->moduleExists('node')) {
    Drupal::configFactory()
      ->getEditable('node.settings')
      ->set('use_admin_theme', TRUE)
      ->save(TRUE);
  }
}