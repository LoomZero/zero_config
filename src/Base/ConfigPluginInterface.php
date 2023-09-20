<?php

namespace Drupal\zero_config\Base;

use Drupal\Core\Form\FormStateInterface;
use Drupal\zero_config\Form\ConfigForm;

interface ConfigPluginInterface {

  public function form(array &$form, FormStateInterface $form_state, ConfigForm $config_form);

  public function submit(array &$form, FormStateInterface $form_state, ConfigForm $config_form): array;

}
