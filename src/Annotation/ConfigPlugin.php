<?php

namespace Drupal\zero_config\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class ConfigPlugin extends Plugin {

  public string $id;

  public string $title;

}
