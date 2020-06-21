<?php

namespace Drupal\migrate_generator;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\migrate_generator\Plugin\GeneratorProcessPluginInterface;
use Drupal\migrate_generator\Annotation\GeneratorProcessPlugin;

/**
 * Plugin manager for generator process plugins.
 *
 * @see \Drupal\migrate_generator\Annotation\GeneratorProcessPlugin
 * @see \Drupal\migrate_generator\Plugin\GeneratorProcessPluginBase
 * @see \Drupal\migrate_generator\Plugin\GeneratorProcessPluginInterface
 * @see plugin_api
 */
class GeneratorProcessPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a new GeneratorProcessPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/migrate_generator/process', $namespaces, $module_handler, GeneratorProcessPluginInterface::class, GeneratorProcessPlugin::class);

    $this->alterInfo('migrate_generator_process_info');
    $this->setCacheBackend($cache_backend, 'migrate_generator_plugins_process');
  }

  /**
   * Check if plugin exists.
   *
   * @param string $plugin_id
   *   A plugin id.
   *
   * @return mixed
   *   A plugin definition, or NULL if the plugin ID is invalid.
   */
  public function isPluginExists($plugin_id) {
    $definition = $this->getDefinition($plugin_id, FALSE);
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // Get fallback to default plugin.
    if (!$this->isPluginExists($plugin_id)) {
      $plugin_id = $this->getFallbackPluginId($plugin_id);
    }
    return parent::createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'default';
  }

}
