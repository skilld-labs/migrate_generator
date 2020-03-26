<?php

namespace Drupal\migrate_generator\Commands;

use Drush\Commands\DrushCommands as DrushCommandsBase;
use Drupal\migrate_generator\Scanner;
use Drupal\migrate_generator\Generator;

/**
 * Defines Drush commands for the migrate_generator module.
 */
class DrushCommands extends DrushCommandsBase {

  /**
   * Migrate source scanner.
   *
   * @var \Drupal\migrate_generator\Scanner
   */
  protected $scanner;

  /**
   * Migrate source generator.
   *
   * @var \Drupal\migrate_generator\Generator
   */
  protected $generator;

  /**
   * Constructor method.
   *
   * @param \Drupal\migrate_generator\Scanner $scanner
   *   Class for migrate source scanner.
   * @param \Drupal\migrate_generator\Generator $generator
   *   Class for migrate source generator.
   */
  public function __construct(Scanner $scanner, Generator $generator) {
    parent::__construct();
    $this->scanner = $scanner;
    $this->generator = $generator;
  }

  /**
   * Generate migrations based on source CSVs.
   *
   * @param string $directory
   *   Path to directory with source .csv files.
   * @param array $options
   *   Additional options for the command.
   *
   * @option pattern
   *   Pattern for source CSV files. Defaults to '*.csv'.
   * @option delimiter
   *   Delimiter for source CSV files. Defaults to ;
   * @option enclosure
   *   Enclosure for source CSV files. Defaults to "
   * @option values_delimiter
   *   Delimiter for multi-valued fields. Defaults to |
   * @option date_format
   *   Date format used in CSV. Defaults to "d-m-Y H:i:s"
   * @option file_source
   *   Type of filepath sources, used in CSV.
   *   Can be "external" (means external URLs to files)
   *     or "absolute" (absolute filepath to local files).
   *   Defaults to "absolute".
   *
   * @command migrate_generator:generate_migrations
   *
   * @usage drush migrate_generator:generate_migrations
   *   Generate migrations from CSV sources from folder.
   */
  public function generateMigrations($directory, array $options = [
    'pattern' => '*.csv',
    'delimiter' => ';',
    'enclosure' => '"',
    'values_delimiter' => '|',
    'date_format' => 'd-m-Y H:i:s',
    'file_source' => 'absolute',
  ]) {
    // Scan source files.
    $sources = $this->scanner->readSources($directory, $options);
    if ($sources) {
      $migration_group = $this->generator->createMigrationGroup();
      foreach ($sources as $entity_type => $entity_info) {
        foreach ($entity_info as $bundle => $source_info) {
          $this->generator->createMigration($entity_type, $bundle, $source_info, $migration_group, $options);
        }
      }
    }
  }

}
