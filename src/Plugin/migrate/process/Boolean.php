<?php

namespace Drupal\migrate_generator\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Convert string into boolean value.
 *
 * @MigrateProcessPlugin(
 *   id = "boolean"
 * )
 */
class Boolean extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
  }

}
