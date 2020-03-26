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

    // Transform date string to Unix timestamp.
    $process[] = [
      'plugin' => 'format_date',
      'from_format' => isset($this->options['date_format']) ? $this->options['date_format'] : 'd-m-Y H:i:s',
      'to_format' => 'U',
    ];

    return $process;
  }

}
