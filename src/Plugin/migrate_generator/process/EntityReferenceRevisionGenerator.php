<?php

namespace Drupal\migrate_generator\Plugin\migrate_generator\process;

/**
 * Generator process plugin for "Entity reference revisions" field type.
 *
 * @GeneratorProcessPlugin(
 *   id = "entity_reference_revisions"
 * )
 */
class EntityReferenceRevisionGenerator extends EntityReferenceGenerator {

  /**
   * {@inheritdoc}
   */
  public function process($field_name) {
    return [
      $field_name . '_tmp' => $this->getBaseProcess($field_name),
      $field_name => [
        'plugin' => 'sub_process',
        'source' => '@' . $field_name . '_tmp',
        'process' => [
          'target_id' => '0',
          'target_revision_id' => '1',
        ],
      ],
    ];
  }

}
