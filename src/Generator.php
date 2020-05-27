<?php

namespace Drupal\migrate_generator;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\Component\Utility\Unicode;

/**
 * Class for migrate generator.
 */
class Generator {

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The generator process plugin manager.
   *
   * @var \Drupal\migrate_generator\GeneratorProcessPluginManager
   */
  protected $processPluginManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructor for Migrate Generator.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\migrate_generator\GeneratorProcessPluginManager $process_plugin_manager
   *   The generator process plugin manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   */
  public function __construct(LoggerInterface $logger, GeneratorProcessPluginManager $process_plugin_manager, FileSystemInterface $file_system) {
    $this->logger = $logger;
    $this->processPluginManager = $process_plugin_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * Create migration group for generated migrations if not exists.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Migration Group object.
   */
  public function createMigrationGroup() {
    // Create migration group for generated migrations if not exists.
    $migration_group = MigrationGroup::load('migrate_generator_group');
    if (empty($migration_group)) {
      $group_properties = [];
      $group_properties['id'] = 'migrate_generator_group';
      $group_properties['label'] = 'Migrate generator group';
      $group_properties['description'] = 'Group for migrations created by Migrate generator module';
      $migration_group = MigrationGroup::create($group_properties);
      $migration_group->save();
    }
    return $migration_group;
  }

  /**
   * Create migration group for generated migrations if not exists.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Migration object or NULL.
   */
  public function createMigration($entity_type, $bundle, $source, $migration_group, $options) {
    $migration_id = $source['migration_id'];
    // Check if migration exists.
    $migration = Migration::load($migration_id);
    if ($migration) {
      if ($options['update']) {
        // Remove existing migration.
        $migration->delete();
        unset($migration);
      }
      else {
        $this->logger->notice(
          'Migration %migration already exists. Skipping.',
          ['%migration' => $migration_id]
        );
      }
    }
    if (empty($migration)) {
      $migrate_properties = [
        'id' => $migration_id,
        'label' => Unicode::ucfirst($entity_type) . ':' . Unicode::ucfirst($bundle),
        'status' => TRUE,
        'migration_group' => $migration_group->id(),
        'migration_tags' => ['migrate_generator'],
        'source' => $this->setSource($source, $options),
        'destination' => $this->setDestination($entity_type, $bundle),
      ];
      $migration_dependencies = [];
      $migration_process = [];
      foreach ($source['fields'] as $field_name => $field_info) {
        $field_info['options'] = $options;
        try {
          $plugin = $this->processPluginManager->createInstance($field_info['field_type'], $field_info);
          // Prepare file destination folder.
          if (in_array($field_info['field_type'], ['file', 'image'])) {
            // Use destination schema storage field settings.
            $directory = $field_info['field_storage_definition']->getSetting('uri_scheme') . '://' . $field_name . DIRECTORY_SEPARATOR;
            $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
          }
          // Get migration process for field.
          $migration_process = array_merge($migration_process, $plugin->process($field_name));
          // Check if we have dependencies for this field.
          if ($field_info['is_reference'] && !empty($field_info['dependencies'])) {
            foreach ($field_info['dependencies'] as $dependency) {
              if (!in_array($dependency, $migration_dependencies)) {
                $migration_dependencies[] = $dependency;
              }
            }
          }
        }
        catch (PluginNotFoundException $ex) {
          // Save log that plugin not found for this field type.
          $this->logger->alert(
            'GeneratorProcessPlugin not found for %field_type field type.',
            ['%field_type' => $field_info['field_type']]
          );
        }
      }
      // Set migration Process.
      $migrate_properties['process'] = $migration_process;
      // Set migration Dependencies.
      $migrate_properties['migration_dependencies'] = [
        'required' => $migration_dependencies,
      ];
      $migration = Migration::create($migrate_properties);
      $migration->save();
      return $migration;
    }
    return NULL;
  }

  /**
   * Set source definition for migration.
   *
   * @param array $source
   *   Source array.
   * @param array $options
   *   Source CSV options.
   *
   * @return array
   *   Source definition.
   */
  protected function setSource(array $source, array $options) {
    $fields = [];
    foreach ($source['header'] as $column) {
      $fields[] = [
        'name' => $column,
      ];
    }

    return [
      'plugin' => 'csv',
      'path' => $source['source'],
      'delimiter' => $options['delimiter'],
      'enclosure' => $options['enclosure'],
      'header_row_count' => 1,
      'fields' => $fields,
      'ids' => [$source['id']],
      'constants' => [],
    ];
  }

  /**
   * Set desination definition for migration.
   *
   * @param string $entity_type
   *   Destination entity type.
   * @param string $bundle
   *   Destination bundle.
   *
   * @return array
   *   Destination definition.
   */
  protected function setDestination($entity_type, $bundle) {
    $destination = [
      'plugin' => 'entity:' . $entity_type,
    ];
    if ($entity_type == 'paragraph') {
      $destination['plugin'] = 'entity_reference_revisions:' . $entity_type;
    }
    if ($entity_type != $bundle) {
      $destination['default_bundle'] = $bundle;
    }
    return $destination;
  }

}
