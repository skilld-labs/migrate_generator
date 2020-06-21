<?php

namespace Drupal\migrate_generator\Plugin\migrate_generator\process;

use Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase;

/**
 * Generator process plugin for "Boolean" field type.
 *
 * @GeneratorProcessPlugin(
 *   id = "boolean"
 * )
 */
class BooleanGenerator extends GeneratorProcessPluginBase {

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

    // Transform string to boolean.
    $process[] = [
      'plugin' => 'callback',
      'callable' => 'mb_strtolower',
    ];
    $process[] = [
      'plugin' => 'boolean',
    ];

    return $process;
  }

}
