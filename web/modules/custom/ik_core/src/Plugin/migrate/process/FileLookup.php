<?php
namespace Drupal\ik_core\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Custom process plugin to lookup an imported file and reference it in a media entity.
 * 
 * Example migration configuration:
 * 
  image/target_id:
    -
      plugin: file_lookup
      source: uri
    -
      plugin: skip_on_empty
      method: row
 *
 * @MigrateProcessPlugin(
 *   id = "file_lookup"
 * )
 */
class FileLookup extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $this->query($value); 
  }

  /**
   * Checks for the existence of some value.
   *
   * @param mixed $value
   *   The uri of the file entity to query.
   *
   * @return mixed|null
   *   Entity id if the queried entity exists. Otherwise NULL.
   */
  protected function query($value) {
    $query = \Drupal::entityQuery('file');
    $query->condition('uri', $value);
    $entity_ids = $query->execute();

    if (count($entity_ids) > 0) {
      $fid = array_values($entity_ids)[0];
      return $fid;
    } else {
      return null;
    }
  }
}