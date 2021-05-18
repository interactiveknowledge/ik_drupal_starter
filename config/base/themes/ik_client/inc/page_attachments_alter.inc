<?php

use Drupal\image\Entity\ImageStyle;

function ik_client_page_attachments_alter(&$page) {

  // Alter Metatags
  if (isset($page['#attached']['html_head'])) {
    // Get Current Node
    $currentNode = \Drupal::routeMatch()->getParameter('node');
    $imageValue = null;
    $addTags = [];

    foreach ($page['#attached']['html_head'] as $delta => $tag) {
      if (isset($tag[1])) {
        $name = $tag[1];

        // Unset system_meta_generator, MobileOptimized, HandheldFriendly
        if (
          $name === 'system_meta_generator' || 
          $name === 'MobileOptimized' || 
          $name === 'HandheldFriendly'
        ) {
          unset($page['#attached']['html_head'][$delta]);
        }

        // Unset empty geo_position tags
        if ($name === 'geo_position' && $tag[0]['#attributes']['content'] === ';') {
          unset($page['#attached']['html_head'][$delta]);
        }

        // Unset empty geo_region tags
        if ($name === 'geo_region' && $tag[0]['#attributes']['content'] === '-') {
          unset($page['#attached']['html_head'][$delta]);
        }

        // Unset empty og_street_address tags
        if ($name === 'og_street_address' && $tag[0]['#attributes']['content'] === '') {
          unset($page['#attached']['html_head'][$delta]);
        }

        // If image is set apply it to other image tags.
        // Default pulls from field_social_image
        // If it's not set, we'll grab from field_media
        if ($name === 'og_image_0' && isset($tag[0]['#attributes']['content'])) {
          $imageValue = $tag[0]['#attributes']['content'];
        }

        // viewport tag
        if ($name === 'viewport') {
          $tag[0]['#attributes']['content'] = 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no';
          $page['#attached']['html_head'][$delta] = $tag;
        }
      }
    }

    // If no $imageValue use field_media
    if (!$imageValue && $currentNode) {
      $style = ImageStyle::load('social_media');

      if ($currentNode->hasField('field_media') && $currentNode->field_media->target_id) {
        $imageUri = $currentNode->field_media->entity->image->entity->getFileUri();
        $imageValue = $style->buildUrl($imageUri);

        if ($imageValue) {
          $addTags['og_image_0'] = [
            '#tag' => 'meta',
            '#attributes' => [
              'property' => 'og:image',
              'content' => $imageValue,
            ],
          ];

          // Add other tags (alt, width, height) if exists
          if ($currentNode->field_media->entity->thumbnail->alt) {
            $addTags['og_image_alt'] = [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:alt',
                'content' => $currentNode->field_media->entity->thumbnail->alt,
              ],
            ];
            $addTags['twitter_image_alt'] = [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'twitter:image:alt',
                'content' => $currentNode->field_media->entity->thumbnail->alt,
              ],
            ];
          }

          if ($currentNode->field_media->entity->thumbnail->width) {
            $addTags['og_image_width'] = [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => $currentNode->field_media->entity->thumbnail->width,
              ],
            ];
          }

          if ($currentNode->field_media->entity->thumbnail->height) {
            $addTags['og_image_height'] = [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => $currentNode->field_media->entity->thumbnail->height,
              ],
            ];
          }
        }
      }
    }

    if ($imageValue) {
      $imageTags = [
        'og:image:secure_url',
        'twitter:image'
      ];

      foreach($imageTags as $imgTag) {
        $name = str_replace(':', '_', $imgTag);
        $addTags[$name] = [
          '#tag' => 'meta',
          '#attributes' => [
            'property' => $imgTag,
            'content' => $imgTag === 'og:image:secure_url' ? str_replace('http:', 'https:', $imageValue) : $imageValue,
          ],
        ];
      }
    }

    if (count($addTags) > 0) {
      foreach ($addTags as $name => $tag) {
        $page['#attached']['html_head'][] = [$tag, $name];
      }
    }
  }
}