<?php

namespace Drupal\migrate_generator\Plugin\migrate_generator\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generator process plugin for "Price" field type.
 *
 * @GeneratorProcessPlugin(
 *   id = "commerce_price"
 * )
 */
class CommercePriceGenerator extends GeneratorProcessPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('migrate_generator'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($field_name) {
    $process = [
      $field_name . '_tmp' => $this->getBaseProcess($field_name),
    ];
    $sources = $this->getSources();
    // Check if column for currency_code exists.
    if (isset($sources['currency_code'])) {
      $process[$field_name . '_code_tmp'] = $this->getBaseProcess($field_name, $sources['currency_code']);
      $process[$field_name . '_code_tmp'][] = [
        'plugin' => 'callback',
        'callable' => 'strtoupper',
      ];
    }
    else {
      // Get list of available currencies.
      $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
      // Use first currency code as default.
      $process[$field_name . '_code_tmp'] = [
        'plugin' => 'default_value',
        'default_value' => key($currencies),
      ];
    }
    $process[$field_name] = [
      [
        'plugin' => 'skip_on_empty',
        'method' => 'process',
        'source' => '@' . $field_name . '_tmp',
      ],
      [
        'plugin' => 'commerce_price',
        'currency_code' => '@' . $field_name . '_code_tmp',
      ],
    ];
    return $process;
  }

}
