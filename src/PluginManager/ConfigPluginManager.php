<?php

namespace Drupal\zero_config\PluginManager;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\file\FileInterface;
use Drupal\zero_cache\Service\ZeroCacheService;
use Traversable;

/**
 * Provides an Archiver plugin manager.
 *
 * @see \Drupal\Core\Archiver\Annotation\Archiver
 * @see \Drupal\Core\Archiver\ArchiverInterface
 * @see plugin_api
 */
class ConfigPluginManager extends DefaultPluginManager {

  private ZeroCacheService $cache;

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
    $this->cache = Drupal::service('zero_cache.service');
  }

  public function getCacheTag(string $key) {
    if (str_starts_with($key, 'zero_config.')) return $key;
    $split = explode('.', $key);
    if (!empty($split[1])) {
      return 'zero_config.' . $split[0] . '.' . $split[1];
    } else {
      return 'zero_config.' . $split[0];
    }
  }

  public function applyCacheTags(&$render_array, string ...$keys) {
    if (empty($render_array['#cache']['tags'])) {
      $render_array['#cache']['tags'] = [];
    }
    foreach ($keys as $index => $key) $keys[$index] = $this->getCacheTag($key);
    $render_array['#cache']['tags'] = Cache::mergeTags($render_array['#cache']['tags'], $keys);
  }

  public function getStates(): array {
    return Drupal::state()->get('zero_config', []);
  }

  public function getState(string $key, array $states = NULL) {
    if ($states === NULL) $states = $this->getStates();
    $this->cache->cacheAddTags([$this->getCacheTag($key)]);
    if (!isset($states[$key])) return NULL;
    return $states[$key];
  }

  public function hasState(string $key, array $states = NULL): bool {
    return $this->getState($key, $states) !== NULL;
  }

  public function getBody(string $field, array $states = NULL): ?array {
    $value = $this->getState($field . '.value', $states);
    if ($value === NULL) return NULL;
    return [
      '#type' => 'processed_text',
      '#text' => $value,
      '#format' => $this->getState($field . '.format', $states),
    ];
  }

  public function getImageStyle(string $field, string $image_style = 'thumbnail', int $index = 0, array $states = NULL): ?array {
    $id = $this->getState($field . '.' . $index, $states);
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
