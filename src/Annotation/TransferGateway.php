<?php

namespace Drupal\account\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Transfer gateway item annotation object.
 *
 * @see \Drupal\account\Plugin\TransferGatewayManager
 * @see plugin_api
 *
 * @Annotation
 */
class TransferGateway extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

}
