<?php

namespace Drupal\migrate_generator\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * The base class for all migrate generator process plugins.
 *
 * @see \Drupal\migrate_generator\GeneratorPluginManager
 * @see \Drupal\migrate_generator\Plugin\GeneratorProcessPluginInterface
 * @see \Drupal\migrate_generator\Annotation\GeneratorProcessPluginManager
 * @see plugin_api
 *
 * @ingroup migration
 */
abstract class GeneratorProcessPluginBase extends PluginBase implements GeneratorProcessPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Field storage definition.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $fieldStorageDefinition;

  /**
   * Source info for this field.
   *
   * @var array
   */
  protected $sources;

  /**
   * Generator options.
   *
   * @var array
   */
  protected $options;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldStorageDefinition = $configuration['field_storage_definition'];
    $this->sources = $configuration['source'];
    $this->options = $configuration['options'];
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('migrate_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseProcess($field_name, $source = NULL) {
    $process = [];

    if (is_null($source)) {
      $source = $this->getDefaultSourceValue();
    }

    $process[] = [
      'plugin' => 'get',
      'source' => $source,
    ];

    $process[] = [
      'plugin' => 'skip_on_empty',
      'method' => 'process',
    ];

    if ($this->getFieldStorageDefinition()->getCardinality() != 1) {
      // Add process for multiple item.
      $process[] = [
        'plugin' => 'explode',
        'delimiter' => isset($this->options['values_delimiter']) ? $this->options['values_delimiter'] : '|',
      ];
    }
    $process[] = [
      'plugin' => 'callback',
      'callable' => 'trim',
    ];
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function process($field_name) {
    $process = [];
    foreach ($this->getSources() as $property => $source) {
      $process_field_name = $field_name;
      if (is_string($property)) {
        // Check if this field type has such property.
        $properties = $this->getFieldStorageDefinition()->getPropertyDefinitions();
        if (!isset($properties[$property])) {
          // Save log that property not found for this field type.
          $this->logger->alert(
            'Property %property not found for field "%field".',
            [
              '%property' => $property,
              '%field' => $field_name,
            ]
          );
          continue;
        }
        $process_field_name = $field_name . '/' . $property;
      }
      $process[$process_field_name] = $this->getBaseProcess($field_name, $source);
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultSourceValue() {
    $properties = $this->getFieldStorageDefinition()->getPropertyDefinitions();
    $default_property = key($properties);

    if ($this->sources) {
      if (count($this->sources) == 1) {
        return reset($this->sources);
      }
      elseif (isset($this->sources[$default_property])) {
        return $this->sources[$default_property];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    return $this->sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinition() {
    return $this->fieldStorageDefinition;
  }

}
