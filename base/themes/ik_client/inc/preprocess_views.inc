<?php

function ik_client_preprocess_views_view(&$variables) {
  $view = $variables['view'];
  $name = $view->storage->id();
}

function ik_client_preprocess_views_view_fields(&$variables) {
  $view = $variables['view'];
  $name = $view->storage->id();
  $currentDisplay = $view->current_display;
}