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
   * @option update
   *   Update previously-generated migrations.
   * @option migrate_group
   *   Migration Group Id.
   *
   * @command migrate_generator:generate_migrations
   *
   * @usage drush migrate_generator:generate_migrations $(pwd)/web/modules/custom/migrate_generator/example
   *   Generate migrations from CSV sources from example folder.
   */
  public function generateMigrations($directory, array $options = [
    'pattern' => '*.csv',
    'delimiter' => ';',
    'enclosure' => '"',
    'values_delimiter' => '|',
    'date_format' => 'd-m-Y H:i:s',
    'update' => FALSE,
    'migrate_group' => 'migrate_generator_group',
  ]) {
    // Scan source files.
    $sources = $this->scanner->readSources($directory, $options);
    if ($sources) {
      $migration_group = $this->generator->createMigrationGroup($options['migrate_group']);
      foreach ($sources as $entity_type => $entity_info) {
        foreach ($entity_info as $bundle => $source_info) {
          $migration = $this->generator->createMigration($entity_type, $bundle, $source_info, $migration_group, $options);
          if ($migration) {
            $this->logger()->notice('Migration ' . $migration->id() . ' was created');
          }
        }
      }
    }
    $this->logger()->notice(
      'Generation of migrations was completed. You can now run them using next command:
drush migrate:import --all --group=' . $options['migrate_group']
    );
  }

}
