<?php

namespace Drupal\migrate_generator\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a price array from the input value.
 *
 * Build a keyed array for price field.
 * If there is no price value, an empty array is returned.
 * Also you can provide value for currency_code.
 *
 * Example:
 * @code
 * price:
 *   plugin: commerce_price
 *   currency_code: currency_code
 *   source: price
 *     -
 * @endcode
 *
 * When price = 12.00 and code is 'CAD', a keyed array, where 'number' => 12.00
 * and 'currency_code => 'CAD'.
 *
 * @MigrateProcessPlugin(
 *   id = "commerce_price",
 *   handle_multiples = TRUE
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
    $processed_values = [];
    if (!empty($value)) {
      if (is_string($value)) {
        $value = [$value];
      }
      // Use first currency code as default.
      $currency_values = key($currencies);
      if (!empty($this->configuration['currency_code'])) {
        $currency_values = $row->get($this->configuration['currency_code']);
      }
      foreach ($value as $key => $item) {
        $currency = $currency_values;
        if (is_array($currency_values)) {
          if (isset($currency_values[$key])) {
            $currency = $currency_values[$key];
          }
          else {
            $currency = reset($currency_values);
          }
        }
        // Check if currency code exists.
        if (!in_array($currency, $currency_codes)) {
          // Skip processing of current value and log message.
          $message = $this->t('Provided currency code %code not exists', ['%code' => $currency]);
          $migrate_executable->saveMessage($message);
          continue;
        }

        $processed_values[] = [
          'number' => $item,
          'currency_code' => $currency,
        ];
      }
    }
    return $processed_values;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
