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
  $tasks['ik_drupal_starter_set_metatag_defaults'] = [];

  return $tasks;
}

/**
 * Sets the default and administration themes.
 */
function ik_drupal_starter_set_default_theme() {
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

function ik_drupal_starter_set_metatag_defaults() {
  if (Drupal::moduleHandler()->moduleExists('metatag')) {
    Drupal::configFactory()
      ->getEditable('metatag.metatag_defaults.global')
      ->set('tags', [
        'title' => '[current-page:title] | [site:name]',
        'content_language' => 'en-US',
        'canonical_url' => '[current-page:url]',
        'og_type' => 'website',
        'og_url' => '[current-page:url]',
        'og_site_name' => '[site:name]',
        'og_title' => '[current-page:title]',
        'twitter_cards_type' => 'summary_large_image',
      ])
      ->save(TRUE);

    Drupal::configFactory()
      ->getEditable('metatag.metatag_defaults.node')
      ->set('tags', [
        'title' => '[node:title] | [site:name]',
        'canonical_url' => '[node:url]',
        'description' => '[node:summary]',
        'og_type' => 'website',
        'og_image_width' => '[node:field_media:0:entity:image:0:social_media:width]',
        'og_image' => '[node:field_media:0:entity:image:0:social_media:url]',
        'og_image_height' => '[node:field_media:0:entity:image:0:social_media:height]',
        'og_image_alt' => '[node:field_media:0:entity:image:0:alt]',
        'og_image_secure_url' => '[current-page:metatag:og_image_url]',
        'og_url' => '[node:url]',
        'og_description' => '[current-page:metatag:description]',
        'og_site_name' => '[site:name]',
        'og_title' => '[node:title]',
        'twitter_cards_image' => '[current-page:metatag:og_image_url]',
        'twitter_cards_image_width' => '[current-page:metatag:og_image_width]',
        'twitter_cards_page_url' => '[node:url]',
        'twitter_cards_description' => '[current-page:metatag:og_description]',
        'twitter_cards_image_alt' => '[current-page:metatag:og_image_alt]',
        'twitter_cards_type' => 'summary_large_image',
        'twitter_cards_image_height' => '[current-page:metatag:og_image_height]',
        'twitter_cards_title' => '[current-page:metatag:og_title]',
      ])
      ->save(TRUE);
  }
}