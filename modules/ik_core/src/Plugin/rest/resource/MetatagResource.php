<?php

namespace Drupal\ik_core\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\node\Entity\Node;

/**
 * Provides a Metatag Resource
 *
 * @RestResource(
 *   id = "ik_metatag_resource",
 *   label = @Translation("IK Metatag Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/metatags"
 *   }
 * )
 */
class MetatagResource extends ResourceBase {

  protected function getBaseRouteRequirements($method) {
    // Change permissions to published content. 
    if ($method === 'GET') {
      $requirements = ['_permission' => 'access content'];
    }
   
    return $requirements;
  }
  
  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get() {
    $params = \Drupal::request()->query->all();
    if ($params['entity']) {
      $entityManager = \Drupal::entityTypeManager()->getStorage($params['entity']);
      $node = $entityManager->load($params['nid']);
    } else {
      $node = Node::load($params['nid']);
    }

    $response = ['data' => $node->metatag];
    return new ResourceResponse($response);
  }
}