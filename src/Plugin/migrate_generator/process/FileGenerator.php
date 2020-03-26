<?php

namespace Drupal\migrate_generator\Plugin\migrate_generator\process;

use Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase;

/**
 * Generator process plugin for "File" field type.
 *
 * @GeneratorProcessPlugin(
 *   id = "file"
 * )
 */
class FileGenerator extends GeneratorProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBaseProcess($field_name, $source = NULL) {
    $process = parent::getBaseProcess($field_name, $source);

    $process[] = [
      'plugin' => 'skip_on_file_not_exists',
    ];

    switch ($this->options['file_source']) {
      case 'external':
        $process_plugin = 'do wnload';
        break;

      case 'absolute':
        $process_plugin = 'file_copy';
        break;

    }

    $process[] = [
      'plugin' => 'sub_process',
      'source' => [
        'constants/' . $field_name . '_destination',
        'null',
      ],
      'process' => [
        'source' => [
          'plugin' => 'get',
          'source' => [
            1,
          ],
        ],
        'filename' => [
          'plugin' => 'callback',
          'callable' => 'basename',
          'source' => [
            1,
          ],
        ],
        'destination' => [
          'plugin' => 'concat',
          'source' => [
            0,
            '@filename',
          ],
        ],
        'final_file' => [
          'plugin' => $process_plugin,
          'source' => [
            '@source',
            '@destination',
          ],
          'file_exists' => 'rename',
        ],
        'target_id' => [
          'plugin' => 'entity_generate',
          'entity_type' => 'file',
          'value_key' => 'uri',
          'source' => '@final_file',
        ],
      ],
    ];

    return $process;
  }

}
