<?php

namespace Drupal\ik_core\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "serializer_json_api",
 *   title = @Translation("Serializer which follows JSON API specs."),
 *   help = @Translation("Serializes views row data matching JSON Api specs. http://jsonapi.org"),
 *   display_types = {"data"}
 * )
 */
class SerializerJsonApi extends Serializer {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $display = $this->view->getDisplay();
    $path = $host . '/' . $display->display['display_options']['path'] . '?';

    /*
     * If the Data Entity row plugin is used, this will be an array of entities
     * which will pass through Serializer to one of the registered Normalizers,
     * which will transform it to arrays/scalars.
     * If the Data field row plugin is used, $rows will not contain objects
     * and will pass directly to the Encoder.
     */

    $data = [];

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $data[] = ['attributes' => $this->view->rowPlugin->render($row)];
    }
    unset($this->view->row_index);

    $params = \Drupal::request()->query->all();

    if (isset($params['_wrapper_format'])) {
      unset($params['_wrapper_format']);
    }

    if (!isset($params['_format'])) {
      $params['_format'] = 'json';
    }

    $offset = isset($params['offset']) ? $params['offset'] : $this->view->pager->getOffset();
    $size = $this->view->pager->getItemsPerPage();

    if (!isset($params['offset'])) {
      $params['offset'] = 0;
    }
  
    if (!isset($params['limit'])) {
      $params['limit'] = $size;
    }

    // Adds in additional pager details in case we want to show data in results (ex: "Showing 10 results of 91 total")
    $current = (int) $params['offset'] / $params['limit'];
    $pages = (int) $this->view->pager->getPagerTotal() + $current;

    $links = [
      'self' => $path . http_build_query($params),
    ];

    if ($current > 0) {
      $links['first'] = $path . http_build_query(array_merge(
        $params, ['offset' => 0, 'limit' => $size]
      ));
      $links['prev'] = $path . http_build_query(array_merge(
        $params, ['offset' => max($offset - $size, 0), 'limit' => $size]
      ));
    }

    $total = (int) $this->view->pager->total_items;

    if ($this->view->pager->hasMoreRecords()) {
      $links['next'] = $path . http_build_query(array_merge(
        $params, ['offset' => $offset + $size, 'limit' => $size]
      ));

      // Show the last link when there is a next option.
      $links['last'] = $path . http_build_query(array_merge(
        $params, ['offset' => (ceil($total / $size) - 1) * $size, 'limit' => $size]
      ));
    }
    else {
      $links['last'] = $path . http_build_query(array_merge(
        $params, ['offset' => (ceil($total / $size) - 1) * $size, 'limit' => $size]
      ));
    }

    $pager = [ 
      'current' => $current,
      'items' => $total + ($current * $params['limit']),
      'pages' => $pages,
      'offset' => $offset
    ];

    // Add in our exposed filters for output on front-end form.
    $filters = [];
    foreach ($this->view->filter as $f => $filter) {
      if ($filter->isExposed()) {
        $type = 'textfield';

        $filters[$f] = $filter->options;

        // @TODO Determine if we need more filtering based on type. 
        // Not sure what all the possible values for type are.
        if (isset($filter->options['type']))
          $type = $filter->options['type'];
          
          
        $filters[$f] = [
          'type' => $type,
          'name' => $filter->options['expose']['identifier'],
          'value' =>  $filter->options['value'],
          'label' => $filter->options['expose']['label'],
          'placeholder' =>  isset($filter->options['expose']['placeholder']) ? $filter->options['expose']['placeholder'] : null,
          'required' =>  $filter->options['expose']['required'],
          'multiple' => $filter->options['expose']['multiple'],
          'filters' => $filter->options,
        ];

        // Taxonomy. 
        if ($filter->options['plugin_id'] === 'taxonomy_index_tid') {
          $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($filter->options['vid']);
          foreach ($terms as $term) {
            $options[] = [
              'label' => $term->name,
              'value' => $term->tid,
            ];
          }

          $filters[$f]['options'] = $options;
        }

        // Date Time Field.
        if ($filter->options['plugin_id'] === 'datetime') {
          $filters[$f]['value'] =  '';
          $filters[$f]['type'] = 'date';
          // @TODO add min/max options for datepicker settings
          // $filter->options['value']['min] 
        }

        // Geolocation: Proximity
        if ($filter->options['plugin_id'] === 'geolocation_filter_proximity') {
          $filters[$f]['type'] = 'geolocation';
          $filters[$f]['proximity_source'] = $filter->options['proximity_source'];

          // Assumes that we're not exposing the proximity units.
          $filters[$f]['proximity_units'] = $filter->options['proximity_units'];

          // @TODO there are so many customizations we could add in possibly. 
        }
      }
    }


    $result = [
      'data' => $data,
      'links' => $links,
      'meta' => $pager,
      'filters' => $filters,
    ];

    return $this->serializer->serialize($result, $display->getContentType());
  }

}
