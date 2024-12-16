<?php

namespace Drupal\account\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Ledger entities.
 *
 * @ingroup account
 */
interface LedgerInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * 借记，进项.
   */
  const string AMOUNT_TYPE_DEBIT = 'debit';

  /**
   * 贷记，出项.
   */
  const string AMOUNT_TYPE_CREDIT = 'credit';

  /**
   * The account.
   */
  public function getAccount(): Account;

  /**
   * The account id.
   */
  public function getAccountId(): int;

  /**
   * The amount type.
   */
  public function getAmountType(): string;

  /**
   * The amount.
   */
  public function getAmount(): Price;

  /**
   * The balance.
   */
  public function getBalance(): Price;

  /**
   * The account type.
   */
  public function getAccountType(): string;

  /**
   * Gets the Ledger creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ledger.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Ledger creation timestamp.
   *
   * @param int $timestamp
   *   The Ledger creation timestamp.
   *
   * @return \Drupal\account\Entity\LedgerInterface
   *   The called Ledger entity.
   */
  public function setCreatedTime(int $timestamp): LedgerInterface;

}
