<?php

namespace Drupal\migrate_generator\Plugin\migrate_generator\process;

use Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase;

/**
 * Generator process plugin for "Entity reference" field type.
 *
 * @GeneratorProcessPlugin(
 *   id = "entity_reference"
 * )
 */
class EntityReferenceGenerator extends GeneratorProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBaseProcess($field_name, $source = NULL) {
    $process = parent::getBaseProcess($field_name, $source);

    if (!empty($this->configuration['dependencies'])) {
      $process[] = [
        'plugin' => 'migration_lookup',
        'migration' => $this->configuration['dependencies'],
        'no_stub' => 'true',
      ];
    }

    return $process;
  }

}
