<?php

namespace Drupal\migrate_generator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a migrate generator process plugin annotation object.
 *
 * Plugin Namespace: Plugin\migrate_generator\process.
 *
 * @see \Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase
 * @see \Drupal\migrate_generator\Plugin\DataParserPluginInterface
 * @see \Drupal\migrate_generator\GeneratorProcessPluginManager
 * @see plugin_api
 *
 * @ingroup migration
 *
 * @Annotation
 */
class GeneratorProcessPlugin extends Plugin {

  /**
   * The plugin ID = Type of processed field.
   *
   * @var string
   */
  public $id;

}
