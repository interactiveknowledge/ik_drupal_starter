<?php

namespace Drupal\unpublished_nodes_redirect\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\node\Entity\Node;
use Drupal\unpublished_nodes_redirect\Utils\UnpublishedNodesRedirectUtils as Utils;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Unpublished Nodes Redirect On 403 Subscriber class.
 */
class UnpublishedNodesRedirectOn403Subscriber extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Fires redirects whenever a 403 meets the criteria for unpublished nodes.
   *
   * @param GetResponseForExceptionEvent $event
   *
   * @see Utils::checksBeforeRedirect for criteria relating to if a node
   * unpublished node should be redirected.
   *
   */
  public function on403(GetResponseForExceptionEvent $event) {
    if ($event->getRequest()->attributes->get('node') != NULL) {
      $nid = \Drupal::routeMatch()->getRawParameter('node');
      $node = Node::load($nid);
      $node_type = $node->getType();
      $is_published = $node->isPublished();
      $config = \Drupal::config('unpublished_nodes_redirect.settings');
      $is_anonymous = \Drupal::currentUser()->isAnonymous();
      // Get the redirect path for this node type.
      $redirect_path = $config->get(Utils::getNodeTypeKey($node_type));
      // Get the response code for this node type.
      $response_code = $config->get(Utils::getResponseCodeKey($node_type));
      if (Utils::checksBeforeRedirect($is_published, $is_anonymous, $redirect_path, $response_code)) {
        $metadata = CacheableMetadata::createFromObject($node)
          ->addCacheableDependency($config)
          ->addCacheTags(['rendered']);
        $response = new TrustedRedirectResponse($redirect_path, $response_code);
        $response->addCacheableDependency($metadata);
        // Set response as not cacheable, otherwise browser will cache it.
        $response->setCache(['max_age' => 0]);
        $event->setResponse($response);
      }
    }
  }

}
