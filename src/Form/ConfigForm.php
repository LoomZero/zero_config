<?php

namespace Drupal\zero_config\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\zero_config\PluginManager\ConfigPluginManager;

class ConfigForm extends ConfigFormBase {

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

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var ConfigPluginManager $manager */
    $manager = Drupal::service('plugin.manager.zero_config');

    $form['#tree'] = TRUE;

    // add plugins
    foreach ($manager->getDefinitions() as $id => $definition) {
      $form[$id] = [
        '#type' => 'details',
        '#title' => isset($definition['title']) ? $definition['title'] . ' (' . $definition['id'] . ')' : $definition['id'],
        '#description' => 'ID: ' . $definition['id'] . ' CLASS: ' . $definition['class'],
      ];

      /** @var Drupal\zero_config\Base\ConfigPluginInterface $plugin */
      $plugin = $manager->createInstance($id, $definition);

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

    Drupal::state()->set('zero_config', $states);
    parent::submitForm($form, $form_state);
  }

  public function getStates(): array {
    return Drupal::state()->get('zero_config', []);
  }

  public function prepareFields($group, &$form) {
    foreach (Element::children($form) as $index) {
      if (isset($form[$index]['#default_value'])) continue;
      switch ($form[$index]['#type']) {
        case 'text_format':
          $form[$index]['#format'] = $this->getStates()[$group . '.' . $index . '.format'] ?? $form[$index]['#format'];
          $form[$index]['#default_value'] = $this->getStates()[$group . '.' . $index . '.value'] ?? NULL;
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