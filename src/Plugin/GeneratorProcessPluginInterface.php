<?php

namespace Drupal\migrate_generator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for migrate generator process plugin.
 *
 * @see \Drupal\migrate_generator\Annotation\GeneratorProcessPlugin
 * @see \Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase
 * @see \Drupal\migrate_generator\GeneratorProcessPluginManager
 * @see plugin_api
 */
interface GeneratorProcessPluginInterface extends PluginInspectionInterface {

  /**
   * Performs the associated process.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   The process array.
   */
  public function process($field_name);

  /**
   * Get basic process.
   *
   * @param string $field_name
   *   Field name.
   * @param string|null $source
   *   Source name.
   *
   * @return array
   *   The process array.
   */
  public function getBaseProcess($field_name, $source = NULL);

  /**
   * Get sources for this field.
   *
   * @return mixed
   *   The sources array.
   */
  public function getSources();

  /**
   * Get field storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   Field storage definition.
   */
  public function getFieldStorageDefinition();

}
