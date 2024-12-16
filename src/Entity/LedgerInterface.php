<?php

namespace Drupal\account\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Ledger entities.
 *
 * @ingroup account
 */
interface LedgerInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Ledger creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ledger.
   */
  public function getCreatedTime();

  /**
   * Sets the Ledger creation timestamp.
   *
   * @param int $timestamp
   *   The Ledger creation timestamp.
   *
   * @return \Drupal\account\Entity\LedgerInterface
   *   The called Ledger entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * @return \Drupal\commerce_price\Price
   */
  public function getBalance();

  /**
   * @return string
   */
  public function getAmountType();

  /**
   * @return \Drupal\commerce_price\Price
   */
  public function getAmount();

  /**
   * @return Account
   */
  public function getAccount();

  /**
   * @return int
   */
  public function getAccountId();

  /**
   * @return string
   */
  public function getAccountType();

}
