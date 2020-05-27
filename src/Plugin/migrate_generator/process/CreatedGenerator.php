<?php

namespace Drupal\migrate_generator\Plugin\migrate_generator\process;

use Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase;

/**
 * Generator process plugin for "Created" field type.
 *
 * @GeneratorProcessPlugin(
 *   id = "created"
 * )
 */
class CreatedGenerator extends GeneratorProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBaseProcess($field_name, $source = NULL) {
    $process = parent::getBaseProcess($field_name, $source);

    // Do not use 'skip_on_empty' process here.
    foreach ($process as $key => $process_item) {
      if ($process_item['plugin'] == 'skip_on_empty') {
        unset($process[$key]);
      }
    }

    $date_format = isset($this->options['date_format']) ? $this->options['date_format'] : 'd-m-Y H:i:s';
    // Add current date as default value.
    $process[] = [
      'plugin' => 'default_value',
      'default_value' => date($date_format),
    ];

    // Transform date string to Unix timestamp.
    $process[] = [
      'plugin' => 'format_date',
      'from_format' => $date_format,
      'to_format' => 'U',
    ];

    return $process;
  }

}
