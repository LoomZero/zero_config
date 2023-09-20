<?php

namespace Drupal\zero_config\PluginManager;

use Drupal;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\file\FileInterface;
use Traversable;

/**
 * Provides an Archiver plugin manager.
 *
 * @see \Drupal\Core\Archiver\Annotation\Archiver
 * @see \Drupal\Core\Archiver\ArchiverInterface
 * @see plugin_api
 */
class ConfigPluginManager extends DefaultPluginManager {

  /**
   * Constructs a ArchiverManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ZeroConfig',
      $namespaces,
      $module_handler,
      'Drupal\zero_config\Base\ConfigPluginInterface',
      'Drupal\zero_config\Annotation\ConfigPlugin'
    );
    $this->alterInfo('zero_config_info');
    $this->setCacheBackend($cache_backend, 'zero_config_info_plugins');
  }

  public function getStates(): array {
    return Drupal::state()->get('zero_config', []);
  }

  public function getBody(array $states, string $field): ?array {
    if (empty($states[$field . '.value'])) return NULL;
    return [
      '#type' => 'processed_text',
      '#text' => $states[$field . '.value'],
      '#format' => $states[$field . '.format'],
    ];
  }

  public function getImageStyle(array $states, string $image_style, string $field, int $index = 0): ?array {
    $id = $states[$field . '.' . $index] ?? NULL;
    if (empty($id)) return NULL;
    $file = Drupal::entityTypeManager()->getStorage('file')->load($id);
    if (!$file instanceof FileInterface) return NULL;

    return [
      '#theme' => 'image_style',
      '#path' => $file->getFileUri(),
      '#style_name' => $image_style,
      '#alt' => $file->getFilename(),
      '#title' => $file->getFilename(),
    ];
  }

}
