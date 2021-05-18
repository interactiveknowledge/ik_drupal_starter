<?php

namespace Drupal\ik_electron_logger\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Metatag Resource
 *
 * @RestResource(
 *   id = "ik_electron_logger_resource",
 *   label = @Translation("Electron Logger Resource"),
  *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/api/electron-logger"
 *   }
 * )
 */
class ElectronLoggerResource extends ResourceBase {
  
  /**
   * Responds to entity POST requests.
   *
   * Takes the post request and sends it
   * to Constant Contact API endpoints.
   *
   * @param array $data
   *   Form data.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function post(array $data) {
    if ($data) {
      $database = \Drupal::database();
      $query = $database->insert('electron_logger')->fields($data)->execute();
      return new ResourceResponse(json_encode('success'));
    }
  }
}