<?php

/**
 * @file
 * New Relic Drush integration.
 */

/**
 * Implements hook_drush_init().
 */
function new_relic_rpm_drush_init() {
  if (\Drupal::hasService('new_relic_rpm.adapter')) {
    /** @var \Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface $adapter */
    $adapter = \Drupal::service('new_relic_rpm.adapter');

    // Add custom parameter, telling the drush command executed.
    $command = drush_get_command();
    $drush_command = array_merge([$command['command']], $command['arguments']);
    $adapter->addCustomParameter('Drush command', implode(' ', $drush_command));

    // Set job state.
    $adapter->setTransactionState(\Drupal::config('new_relic_rpm.settings')->get('track_drush'));
  }
}

/**
 * Implements hook_drush_command().
 */
function new_relic_rpm_drush_command() {
  $items = [];

  $items['newrelic-deploy'] = [
    'description' => dt('Notify New Relic of a deployment.'),
    'arguments' => [
      'revision' => dt('Optional. Revision id of the deployment.'),
      'description' => dt('Optional. A brief description of the deployment.'),
      'user' => dt('Optional. User doing the deploy.'),
      'changelog' => dt('Optional. A list of changes for this deployment.'),
    ],
    'aliases' => ['nrd'],
  ];

  return $items;
}

/**
 * Mark a deployment in newrelic.
 */
function drush_new_relic_rpm_newrelic_deploy($revision, $description = NULL, $user = NULL, $changelog = NULL) {

  /** @var \Drupal\new_relic_rpm\Client\NewRelicApiClient $client */
  $client = \Drupal::service('new_relic_rpm.client');
  $deployment = $client->createDeployment($revision, $description, $user, $changelog);

  if ($deployment) {
    drush_log(dt('New Relic deployment created successfully.'), 'success');
  }
  else {
    drush_log(dt('New Relic deployment failed.'), 'error');
  }
}
