<?php

namespace Drupal\zero_config\Form;

use Closure;
use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\zero_config\PluginManager\ConfigPluginManager;

class ConfigForm extends ConfigFormBase {

  private ?ConfigPluginManager $manager = NULL;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zero_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'zero_config',
    ];
  }

  public function getManager(): ConfigPluginManager {
    if ($this->manager === NULL) {
      $this->manager = Drupal::service('plugin.manager.zero_config');
    }
    return $this->manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    // add plugins
    foreach ($this->getManager()->getDefinitions() as $id => $definition) {
      $form[$id] = [
        '#type' => 'details',
        '#title' => $definition['title'] ?? $definition['id'],
        '#description' => 'ID: ' . $definition['id'] . ' CLASS: ' . $definition['class'],
      ];

      /** @var Drupal\zero_config\Base\ConfigPluginInterface $plugin */
      $plugin = $this->getManager()->createInstance($id, $definition);

      $plugin->form($form[$id], $form_state, $this);
      $this->prepareFields($id, $form[$id]);
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var ConfigPluginManager $manager */
    $manager = Drupal::service('plugin.manager.zero_config');

    // build states object
    $states = [];
    foreach ($manager->getDefinitions() as $id => $definition) {
      /** @var Drupal\zero_config\Base\ConfigPluginInterface $plugin */
      $plugin = $manager->createInstance($id, $definition);

      $values = $plugin->submit($form[$id], $form_state, $this);
      if (isset($values['state']) && count($values['state'])) {
        foreach ($values['state'] as $field => $value) {
          $states[$id . '.' . $field] = $value;
        }
      }
    }

    // save new states
    Drupal::state()->set('zero_config', $states);

    // check if values have changed
    $existing_states = Drupal::state()->get('zero_config');
    $diff = [];
    foreach ($existing_states as $key => $value) {
      $a = $value;
      $b = isset($states[$key]) ? $states[$key] : NULL;
      if (is_array($a)) $a = json_encode($a);
      if (is_array($b)) $b = json_encode($b);
      if ($a !== $b) $diff[$key] = $value;
    }

    // clear cache for changed values
    $keys = ['zero_config' => 0];
    foreach ($diff as $key => $value) {
      $split = explode('.', $key);
      $keys['zero_config.' . $split[0]] = count($keys);
      $keys['zero_config.' . $split[0] . '.' . $split[1]] = count($keys);
    }
    Cache::invalidateTags(array_flip($keys));

    parent::submitForm($form, $form_state);
  }

  public function getStates(): array {
    return $this->getManager()->getStates();
  }

  public function prepareFields($group, &$form) {
    foreach (Element::children($form) as $index) {
      if (isset($form[$index]['#default_value'])) continue;
      switch ($form[$index]['#type']) {
        case 'text_format':
          $form[$index]['#format'] = $this->getStates()[$group . '.' . $index . '.format'] ?? $form[$index]['#format'];
          $form[$index]['#default_value'] = $this->getStates()[$group . '.' . $index . '.value'] ?? NULL;
          break;
        case 'managed_file':
          $form[$index]['#default_value'] = !empty($this->getStates()[$group . '.' . $index . '.0']) ? [$this->getStates()[$group . '.' . $index . '.0']] : [];
          break;
        default:
          $form[$index]['#default_value'] = $this->getStates()[$group . '.' . $index] ?? NULL;
          break;
      }

      $this->prepareFields($group . '.' . $index, $form[$index]);
    }
  }

  public function getSubmitValues(FormStateInterface $form_state, string $id, bool $with_parent = FALSE) {
    $values = [];
    $this->getSubmitValuesRecursive($values, $with_parent ? $id : NULL, $form_state->getValue($id));
    return $values;
  }

  private function getSubmitValuesRecursive(array &$values, ?string $parent, array $items) {
    foreach ($items as $index => $item) {
      $id = $parent ? $parent . '.' . $index : $index;
      if (is_array($item)) {
        $this->getSubmitValuesRecursive($values, $id, $item);
      } else {
        $values[$id] = $item;
      }
    }
  }

}
