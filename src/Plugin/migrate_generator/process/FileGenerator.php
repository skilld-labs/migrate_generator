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
      'method' => 'process',
    ];

    $process[] = [
      'plugin' => 'to_array',
    ];

    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function process($field_name) {
    $process = [
      $field_name . '_tmp' => $this->getBaseProcess($field_name),
    ];

    switch ($this->options['file_source']) {
      case 'external':
        $process_plugin = 'download';
        break;

      case 'absolute':
        $process_plugin = 'file_copy';
        break;
    }

    $source = '@' . $field_name . '_tmp';
    if ($this->getFieldStorageDefinition()->getCardinality() == 1) {
      $source = [$source];
    }

    $process[$field_name] = [
      'plugin' => 'sub_process',
      'source' => $source,
      'process' => [
        'source' => '0',
        'filename' => [
          'plugin' => 'callback',
          'callable' => 'basename',
          'source' => '0',
        ],
        'target_directory' => [
          'plugin' => 'default_value',
          'default_value' => $this->getTargetDirectory($field_name),
        ],
        'destination' => [
          'plugin' => 'concat',
          'source' => [
            '@target_directory',
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

  /**
   * Get directory name for migrating file.
   */
  private function getTargetDirectory($field_name) {
    return $this->getFieldStorageDefinition()->getSetting('uri_scheme') . '://' . $field_name . DIRECTORY_SEPARATOR;
  }

}
