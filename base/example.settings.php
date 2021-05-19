<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all environments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Skipping permissions hardening will make scaffolding
 * work better, but will also raise a warning when you
 * install Drupal.
 *
 * https://www.drupal.org/project/drupal/issues/3091285
 */
// $settings['skip_permissions_hardening'] = TRUE;

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
    include $local_settings;
}


/**
 *  Start IK Customizations
 */


/**
 * Sync path.
 */
$settings['config_sync_directory'] = '../config/sync';

/**
 * Config Sync/Split paths.
 */
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    $config['config_split.config_split.development']['status'] = FALSE;
    $config['config_split.config_split.production']['status'] = TRUE;
  } else if ($_ENV['PANTHEON_ENVIRONMENT'] === 'test') {
    $config['config_split.config_split.development']['status'] = FALSE;
    $config['config_split.config_split.production']['status'] = TRUE;
  } else {
    $config['config_split.config_split.development']['status'] = TRUE;
    $config['config_split.config_split.production']['status'] = FALSE;
  }
} else {
  $config['config_split.config_split.development']['status'] = TRUE;
  $config['config_split.config_split.production']['status'] = FALSE;
}


/**
 *  Environment indicator settings
 */
$config['environment_indicator.indicator']['fg_color'] = '#fff';

if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    $config['environment_indicator.indicator']['bg_color'] = '#007e33';
    $config['environment_indicator.indicator']['name'] = 'Production';
  } else if ($_ENV['PANTHEON_ENVIRONMENT'] === 'test') {
    $config['environment_indicator.indicator']['bg_color'] = '#ff6329';
    $config['environment_indicator.indicator']['name'] = 'Staging';
  } else {
    $config['environment_indicator.indicator']['bg_color'] = '#cc0000';
    $config['environment_indicator.indicator']['name'] = $_ENV['PANTHEON_ENVIRONMENT'];
  }
} else {
  $config['environment_indicator.indicator']['bg_color'] = '#00695c';
  $config['environment_indicator.indicator']['name'] = 'Local';
}

/**
 *  Search API backend configurations.
 */
// if (isset($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
//   $config['search_api.server.SERVER']['backend_config']['connector_config']['core'] = 'PRODUCTIONCORENAME';
// } else {
//   $config['search_api.server.SERVER']['backend_config']['connector_config']['core'] = 'DEVCORENAME';
// }
