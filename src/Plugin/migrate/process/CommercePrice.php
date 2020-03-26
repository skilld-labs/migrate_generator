<?php

namespace Drupal\migrate_generator\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a price array from the input value.
 *
 * Build a keyed array where price is the first value in the input array and the
 * currency code is the second. If there is no price value, an empty array is
 * returned.
 *
 * Example:
 * @code
 * price:
 *   plugin: commerce_price
 *   source:
 *     - price
 *     - currency_code
 * @endcode
 *
 * When price = 12.00 and code is 'CAD', a keyed array, where 'number' => 12.00
 * and 'currency_code => 'CAD'.
 *
 * @MigrateProcessPlugin(
 *   id = "commerce_price"
 * )
 */
class CommercePrice extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a BlockTheme object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Get list of available currencies.
    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    $currency_codes = array_keys($currencies);

    list($number, $code) = $value;
    $new_value = [];
    if ($number) {
      $new_value['number'] = $number;
    }
    if ($code) {
      $code = strtoupper($code);
      if (in_array($code, $currency_codes)) {
        $new_value['currency_code'] = $code;
      }
      else {
        // Skip processing of current value.
        $message = $this->t('Provided currency code %code not exists', ['%code' => $code]);
        // Log the message.
        $migrate_executable->saveMessage($message);
        throw new MigrateSkipProcessException($message);
      }
    }
    else {
      // Use first currency code as default.
      $new_value['currency_code'] = array_shift($currency_codes);
    }
    return $new_value;
  }

}
