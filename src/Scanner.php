<?php

namespace Drupal\migrate_generator;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;

/**
 * Class for migrate source scanner.
 */
class Scanner {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Source scanner constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityFieldManager $entity_field_manager,
    LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->logger = $logger;
  }

  /**
   * Read directory with source CSV files.
   *
   * @param string $directory
   *   Path to directory with source .csv files.
   * @param array $options
   *   Additional options.
   *
   * @return array
   *   Array of source infos.
   */
  public function readSources($directory, array $options) {
    $sources = [];
    // Read files from specified folder.
    if (!is_dir($directory)) {
      throw new \Exception('The destination directory does not exist.');
    }
    // Look for source files in defined directory.
    $source_files = glob($directory . DIRECTORY_SEPARATOR . $options['pattern']);
    if (empty($source_files)) {
      throw new \Exception('Source files not found in the destination directory.');
    }

    foreach ($source_files as $source_file) {
      // Extract entity type, bundle from the file name. Supported:
      // {entity_type}-{bundle}.csv or {entity_type}.csv.
      preg_match('!
        ([a-z_]+)             # entity type (group 1)
        (
          \\-                 # -
          ([^\\./]+)          # bundle (group 3)
        )?                    # (can be empty)
        \\.                   # .
        csv                   # csv extension
      $!x', $source_file, $matches);
      if (isset($matches[1])) {
        $entity_type = $matches[1];
        $bundle = $matches[3] ?: $entity_type;
      }
      else {
        // Skip if cannot parse source file name.
        $this->logger->alert(
          'Cannot parse source filename %name. Skipping.',
          ['%name' => $source_file]
        );
        continue;
      }

      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      // Skip if bundle not exists.
      if (!($bundle_info && isset($bundle_info[$bundle]))) {
        $this->logger->warning(
          'Bundle %bundle does not exist for %entity entity. Skipping.',
          [
            '%bundle' => $bundle,
            '%entity' => $entity_type,
          ]
        );
        continue;
      }

      // Read source file headers.
      $reader = Reader::createFromPath($source_file, 'r');
      $reader->setDelimiter($options['delimiter']);
      $reader->setEnclosure($options['enclosure']);
      $header = $reader->fetchOne();

      $source_data = [
        'migration_id' => $this->getMigrationId($entity_type, $bundle, $options['migrate_group']),
        'source' => $source_file,
        'header' => $header,
        'id' => reset($header),
      ];
      $sources[$entity_type][$bundle] = $source_data;
    }
    // Once all sources were scanned,
    // we can check field definition and dependencies.
    $this->checkFields($sources);
    return $sources;
  }

  /**
   * Update source array with fields definition and dependencies.
   *
   * @param array $sources
   *   Source info array.
   */
  protected function checkFields(array &$sources) {
    foreach ($sources as $entity_type => &$entity_info) {
      foreach ($entity_info as $bundle => &$source_info) {
        $fields_info = [];
        $instances = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
        foreach ($source_info['header'] as &$column) {
          // Do not use first column
          // It will be used only for internal ID mapping.
          if ($column == $source_info['id']) {
            continue;
          }
          // Check if this field has several properties.
          $item = explode('/', $column);
          $fieldname = $item[0];
          $fieldkey = $item[1] ?: NULL;
          if (isset($fields_info[$fieldname])) {
            $field_info = $fields_info[$fieldname];
          }
          else {
            $field_info = [
              'fieldname' => $fieldname,
              'multiple' => FALSE,
              'is_reference' => FALSE,
            ];
          }
          if ($fieldkey) {
            // Replace '/' with '__' in column name.
            $column = str_replace('/', '__', $column);
            $field_info['source'][$fieldkey] = $column;
          }
          else {
            $field_info['source'][] = $column;
          }

          // Check if we have such field in this entity.
          if (isset($instances[$fieldname])) {
            $field_info['field_type'] = $instances[$fieldname]->getType();
            $field_info['field_storage_definition'] = $instances[$fieldname]->getFieldStorageDefinition();
            $reference_target_type = $target_bundles = NULL;
            // Get target info for field types.
            switch ($field_info['field_type']) {
              case 'image':
              case 'file':
                $reference_target_type = 'file';
                $target_bundles = ['file'];
                break;

              case 'entity_reference':
              case 'entity_reference_revisions':
                $reference_definition = $instances[$fieldname]->getItemDefinition()->getSettings();
                $reference_target_type = $reference_definition['target_type'];
                if (!empty($reference_definition['handler_settings']['target_bundles'])) {
                  $target_bundles = $reference_definition['handler_settings']['target_bundles'];
                }
                else {
                  // Get all bundles for referenced target type.
                  $target_bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($reference_target_type));
                }
                break;
            }
            if ($reference_target_type && $target_bundles) {
              $field_info['is_reference'] = TRUE;
              $field_info['reference_target_type'] = $reference_target_type;
              $field_info['target_bundles'] = $target_bundles;

              // Check if we have sources for referenced entity type.
              if (isset($sources[$reference_target_type])) {
                foreach ($target_bundles as $target_bundle) {
                  // Check if we have sources for referenced bundle.
                  if (isset($sources[$reference_target_type][$target_bundle])
                    && !empty($sources[$reference_target_type][$target_bundle]['migration_id'])) {
                    // If have source then add dependency.
                    $related_migration = $sources[$reference_target_type][$target_bundle]['migration_id'];
                    $field_info['dependencies'][] = $related_migration;
                  }
                }
              }
            }

            if ($instances[$fieldname]->getFieldStorageDefinition()->getCardinality() != 1) {
              $field_info['multiple'] = TRUE;
            }

            $fields_info[$fieldname] = $field_info;
          }
          else {
            $this->logger->warning(
              'Field %field not found for %bundle %entity.',
              [
                '%field' => $fieldname,
                '%bundle' => $bundle,
                '%entity' => $entity_type,
              ]
            );
          }
        }
        $source_info['fields'] = $fields_info;
      }
    }
  }

  /**
   * Generate migration Id.
   *
   * @param string $entity_type
   *   Destination entity type.
   * @param string $bundle
   *   Destination bundle.
   * @param string $migration_group
   *   Migration Group Id.
   *
   * @return string
   *   Migration Id.
   */
  protected function getMigrationId($entity_type, $bundle, $migration_group) {
    return $migration_group . '__' . $entity_type . '__' . $bundle;
  }

}
