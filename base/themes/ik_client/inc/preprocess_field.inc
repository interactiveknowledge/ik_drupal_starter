<?php

function ik_client_preprocess_field(&$variables) {
  // Set parent entity for the field so we can use it in twig.
  if (isset($variables['element']['#object'])) {
    $entity = $variables['element']['#object'];
    $variables['entity'] = $entity;
  }
}

