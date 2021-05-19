<?php

function ik_client_preprocess_input(&$variables) {
  // Remove aria-describedby if no description exists.
  if ($variables['theme_hook_original'] === 'input__checkbox') {
    unset($variables['attributes']['aria-describedby']);
  }
}