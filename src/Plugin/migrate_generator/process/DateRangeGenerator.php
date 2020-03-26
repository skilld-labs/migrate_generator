<?php

namespace Drupal\migrate_generator\Plugin\migrate_generator\process;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase;

/**
 * Generator process plugin for "Date range" field type.
 *
 * @GeneratorProcessPlugin(
 *   id = "daterange"
 * )
 */
class DateRangeGenerator extends GeneratorProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBaseProcess($field_name, $source = NULL) {
    $process = parent::getBaseProcess($field_name, $source);

    // Get target date format from field storage settings.
    $datetime_type = $this->getFieldStorageDefinition()->getSetting('datetime_type');
    if ($datetime_type == DateRangeItem::DATETIME_TYPE_DATETIME) {
      $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    }
    else {
      $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    }
    // Transform date string to proper format.
    $process[] = [
      'plugin' => 'format_date',
      'from_format' => isset($this->options['date_format']) ? $this->options['date_format'] : 'd-m-Y H:i:s',
      'to_format' => $format,
    ];
    return $process;
  }

}
