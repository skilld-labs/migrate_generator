<?php

namespace Drupal\migrate_generator\Plugin;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * The base class for date migrate generator process plugins.
 */
abstract class GeneratorProcessPluginDateBase extends GeneratorProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBaseProcess($field_name, $source = NULL) {
    $process = parent::getBaseProcess($field_name, $source);

    $source_date_format = isset($this->options['date_format']) ? $this->options['date_format'] : 'd-m-Y H:i:s';
    $field_type = $this->getFieldStorageDefinition()->getType();
    $timestamp_types = ['created', 'changed'];
    $datetime_types = ['datetime', 'daterange'];
    $destination_format = 'U';
    if (in_array($field_type, $timestamp_types)) {
      // Do not use 'skip_on_empty' process for 'created' and 'changed' fields.
      foreach ($process as $key => $process_item) {
        if ($process_item['plugin'] == 'skip_on_empty') {
          unset($process[$key]);
        }
      }
      // Use current date as default value.
      $process[] = [
        'plugin' => 'default_value',
        'default_value' => date($source_date_format),
      ];
    }
    elseif (in_array($field_type, $datetime_types)) {
      // Get target date format from field storage settings.
      $datetime_type = $this->getFieldStorageDefinition()->getSetting('datetime_type');
      if ($datetime_type == DateRangeItem::DATETIME_TYPE_DATETIME) {
        $destination_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
      }
      else {
        $destination_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
      }
    }

    // Transform date string to proper format.
    $process[] = [
      'plugin' => 'format_date',
      'from_format' => $source_date_format,
      'to_format' => $destination_format,
    ];

    return $process;
  }

}
