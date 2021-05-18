<?php

namespace Drupal\ik_electron_logger\Controller;

use Drupal\Core\Controller\ControllerBase;


/**
 * A documentation controller.
 */
class ElectronLogsController extends ControllerBase {
  public function content() {
    $applications = $users = $versions = $levels = [0 => 'Select'];
    $params = \Drupal::request()->query->all();

    $headers = [
      ['data' => t('Timestamp'), 'field' => 'timestamp', 'sort' => 'desc'],
      ['data' => t('Level'), 'field' => 'level'],
      ['data' => t('Message'), 'field' => 'message'],
      ['data' => t('Application'), 'field' => 'application'],
      ['data' => t('Version'), 'field' => 'version'],
      ['data' => t('User'), 'field' => 'user']
    ];

    $database = \Drupal::database();
    $query = $database->select('electron_logger', 'e');
    $query->fields('e', ['timestamp', 'level', 'message', 'application', 'version', 'user']);

    if (isset($params['level']) && $params['level'] !== '0') {
      $query->condition('e.level', $params['level']);
    }

    if (isset($params['application']) && $params['application'] !== '0') {
      $query->condition('e.application', $params['application']);
    }

    if (isset($params['version']) && $params['version'] !== '0') {
      $query->condition('e.version', $params['version']);
    }

    if (isset($params['user']) && $params['user'] !== '0') {
      $query->condition('e.user', $params['user']);
    }

    if (isset($params['sort']) && isset($params['order'])) {
      $query->orderBy('e.' . strtolower($params['order']), $params['sort']);
    } else {
      $query->orderBy('e.timestamp', 'desc');
    }

    $sorted = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($headers);
    $pager = $sorted->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);

    $results = $pager->execute();
    $rows = [];

    foreach ($results as $row) {
      if (!in_array($row->application, $applications) && $row->application)
        $applications[$row->application] = $row->application;
      if (!in_array($row->version, $versions))
        $versions[$row->version] = $row->version;
      if (!in_array($row->user, $users))
        $users[$row->user] = $row->user;
      if (!in_array($row->level, $levels))
        $levels[$row->level] = $row->level;

      $rows[] = ['data' => (array) $row];
    }

    $form['#type'] = 'form';
    $form['#method'] = 'get';

    if (array_filter($levels)) {
      asort($levels);
  
      $form['level'] = [
        '#type' => 'select',
        '#title' => t('Level'),
        '#name' => 'level',
        '#options' => $levels,
        '#default_value' => isset($params['level']) ? $params['level'] : 0
      ];
    }

    if (array_filter($applications)) {
      asort($applications);
  
      $form['application'] = [
        '#type' => 'select',
        '#title' => t('Application'),
        '#name' => 'application',
        '#options' => $applications,
        '#default_value' => isset($params['application']) ? $params['application'] : 0
      ];
    }

    if (array_filter($versions)) {
      asort($versions);
  
      $form['version'] = [
        '#type' => 'select',
        '#title' => t('Version'),
        '#name' => 'version',
        '#options' => $versions,
        '#default_value' => isset($params['version']) ? $params['version'] : 0
      ];
    }

    if (array_filter($users)) {
      asort($users);
  
      $form['user'] = [
        '#type' => 'select',
        '#title' => t('User'),
        '#name' => 'user',
        '#options' => $users,
        '#default_value' => isset($params['user']) ? $params['user'] : 0
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Filter')
    ];

    $form['actions']['reset'] = [
      '#type' => 'button',
      '#value' => t('Reset'),
      '#attributes' => ['style' => 'margin-top: 30px;']
    ];

    $form['#attributes']['style'] = 'margin-bottom: 30px;';
    $form['#attributes']['class'][] = 'form--inline clearfix';

    $build = [
      '#title' => 'Electron Logs',
      'form' => $form,
      'table' => [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#empty' => t('There are no logs available')
      ],
      'pager' => [
        '#type' => 'pager'
      ]
    ];

    return $build;
  }
}