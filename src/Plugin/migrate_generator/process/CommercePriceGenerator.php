<?php

namespace Drupal\migrate_generator\Plugin\migrate_generator\process;

use Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase;

/**
 * Generator process plugin for "Price" field type.
 *
 * @GeneratorProcessPlugin(
 *   id = "commerce_price"
 * )
 */
class CommercePriceGenerator extends GeneratorProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function process($field_name) {
    $process = [
      $field_name . '_tmp' => $this->getBaseProcess($field_name),
    ];
    $sources = $this->getSources();
    foreach ($sources as $property => $source) {
      if (is_string($property)) {
        // Check if this field type has such property.
        $properties = $this->getFieldStorageDefinition()->getPropertyDefinitions();
        if (!isset($properties[$property])) {
          unset($sources[$property]);
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
        // Currency column should be last in sources.
        if ($property == 'currency_code') {
          unset($sources[$property]);
          array_push($sources, $source);
        }
      }
    }
    $process[$field_name] = [
      'plugin' => 'commerce_price',
      'source' => array_values($sources),
    ];
    return $process;
  }

}
