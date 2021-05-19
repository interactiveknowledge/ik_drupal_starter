<?php

function ik_client_preprocess_block(&$variables) { 
  if (isset($variables['elements']['content']['#block_content'])) {
    $block = $variables['elements']['content']['#block_content'];

    $variables['block_content'] = [
      'bundle' => $block->bundle(),
    ];
  }
}