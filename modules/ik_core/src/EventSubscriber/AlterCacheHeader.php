<?php

/**
 * @file
 * Contains \Drupal\ik_core\EventSubscriber\AlterCacheHeader.
 */

namespace Drupal\ik_core\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber MyEventSubscriber.
 */
class AlterCacheHeader implements EventSubscriberInterface {

  /**
   * Match headers and adjust response accordingly.
   *
   * @param FilterResponseEvent $event
   * @return void
   */
  public function onRespond(FilterResponseEvent $event) {
    if (preg_match('/\/jsonapi\//', $event->getRequest()->getRequestUri())) {
      $event->getResponse()->setVary('Authorization');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }
}