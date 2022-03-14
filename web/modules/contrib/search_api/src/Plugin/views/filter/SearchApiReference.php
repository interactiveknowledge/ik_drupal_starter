<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\EntityReference;
use Drupal\views\ViewExecutable;

/**
 * Defines a filter for filtering on entity references.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_reference")
 */
class SearchApiReference extends EntityReference {

  use SearchApiFilterTrait;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL): void {
    if (empty($this->definition['field_name'])) {
      $this->definition['field_name'] = $options['field'];
    }

    parent::init($view, $display, $options);
  }

}
