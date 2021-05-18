<?php
namespace Drupal\ik_core\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Custom process plugin to convert youtube scheme uri to video url.
 *
 * @MigrateProcessPlugin(
 *   id = "youtube"
 * )
 */
class Youtube extends ProcessPluginBase {

  const SCHEME = 'youtube://';
  const BASE_URL = 'http://youtube.com/watch?';

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Convert youtube scheme uri to video url.
    if (strpos($value, static::SCHEME) !== FALSE) {
      $value = static::BASE_URL . implode('=', explode('/', str_replace(static::SCHEME, '', $value), 2));
    }
    else {
      $value = NULL;
    }
    return $value;
  }

}