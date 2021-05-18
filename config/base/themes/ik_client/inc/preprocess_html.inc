<?php

function ik_client_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch();
  $node = $route->getParameter('node');
  $media = $route->getParameter('media');
  $taxonomy = $route->getParameter('taxonomy_term');
  $view = $route->getParameter('view_id');
  $view_display_id = $route->getParameter('display_id');

  // Add Layout Selection to Body classes.
  if ($node && $node->layout_selection && $node->layout_selection->getValue()) {
    $selection = $node->layout_selection->getValue();
    $variables['layout_selection'] = $selection[0]['target_id'];
  } else if ($node) {
    $variables['layout_selection'] = $node->bundle() . '-default';
  }

  if ($media) {
    $variables['media'] = $media;
  }

  // Taxonomy 
  if ($taxonomy) {
    $variables['taxonomy'] = $taxonomy;
  }

  // Views 
  if ($view && $view_display_id) {
    $variables['view'] = $view;
    $variables['view_display'] = $view_display_id;
  }
}