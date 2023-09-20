<?php

namespace Drupal\zero_config\Plugin\ZeroConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\zero_config\Annotation\ConfigPlugin;
use Drupal\zero_config\Base\ConfigPluginInterface;
use Drupal\zero_config\Form\ConfigForm;

/**
 * @ConfigPlugin(
 *   id="test"
 * )
 */
class TestConfig implements ConfigPluginInterface {

  public function form(array &$form, FormStateInterface $form_state, ConfigForm $config_form) {
    $form['textfield'] = [
      '#title' => 'Test Textfield',
      '#type' => 'textfield',
    ];

    $form['inner'] = [
      '#type' => 'details',
      '#title' => 'INNER',
    ];

    $form['inner']['textfield'] = [
      '#title' => 'Test Textfield',
      '#type' => 'textfield',
    ];

    $form['inner']['body'] = [
      '#type' => 'text_format',
      '#title' => 'Covid hint on course detail pages (state)',
      '#format' => 'full_html',
    ];
  }

  public function submit(array &$form, FormStateInterface $form_state, ConfigForm $config_form): array {
    return [
      'state' => $config_form->getSubmitValues($form_state, 'test'),
    ];
  }

}
