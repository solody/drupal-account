<?php

namespace Drupal\account\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Account type entities.
 */
interface AccountTypeInterface extends ConfigEntityInterface {

  /**
   * Get the withdrawal period.
   */
  public function getWithdrawPeriod(): int;

  /**
   * Get the minimum limitation of withdrawal.
   */
  public function getMinimumWithdraw(): float;

  /**
   * Get the maximum limitation of withdrawal.
   */
  public function getMaximumWithdraw(): float;

}
