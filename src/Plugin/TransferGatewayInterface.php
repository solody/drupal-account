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
   * 转账
   * @param WithdrawInterface $withdraw
   * @return mixed
   */
  public function transfer(WithdrawInterface $withdraw);
}
