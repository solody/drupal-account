<?php

namespace Drupal\account\Plugin;

use Drupal\entity\BundlePlugin\BundlePluginInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\account\Entity\WithdrawInterface;

/**
 * Defines an interface for Transfer gateway plugins.
 */
interface TransferGatewayInterface extends PluginWithFormsInterface, ConfigurableInterface, PluginFormInterface, BundlePluginInterface {

  /**
   * Do transfer.
   *
   * @param \Drupal\account\Entity\WithdrawInterface $withdraw
   *   The withdrawal to action.
   *
   * @return bool
   *   if Successful.
   */
  public function transfer(WithdrawInterface $withdraw): bool;

}
