<?php

namespace Drupal\migrate_generator\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transform the source value to array.
 *
 * @MigrateProcessPlugin(
 *   id = "to_array"
 * )
 */
class ToArray extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $processed_values = $value;
    if (is_string($value)) {
      $processed_values = [$value];
    }
    return $processed_values;
  }

}
