<?php

namespace Drupal\account\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Withdraw entities.
 *
 * @ingroup account
 */
interface WithdrawInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Withdraw name.
   *
   * @return string
   *   Name of the Withdraw.
   */
  public function getName(): string;

  /**
   * Sets the Withdraw name.
   *
   * @param string $name
   *   The label.
   *
   * @return \Drupal\account\Entity\WithdrawInterface
   *   The called Withdraw entity.
   */
  public function setName(string $name): WithdrawInterface;

  /**
   * Gets the Withdraw transaction_number.
   *
   * @return string
   *   TransactionNumber of the Withdraw.
   */
  public function getTransactionNumber(): string;

  /**
   * Sets the Withdraw transaction_number.
   *
   * @param string $transaction_number
   *   The Withdrawal transaction_number.
   *
   * @return \Drupal\account\Entity\WithdrawInterface
   *   The called Withdrawal entity.
   */
  public function setTransactionNumber(string $transaction_number): WithdrawInterface;

  /**
   * Gets the Withdraw creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Withdraw.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Withdraw creation timestamp.
   *
   * @param int $timestamp
   *   The Withdrawal creation timestamp.
   *
   * @return \Drupal\account\Entity\WithdrawInterface
   *   The called Withdrawal entity.
   */
  public function setCreatedTime(int $timestamp): WithdrawInterface;

  /**
   * The amount.
   */
  public function getAmount(): Price;

  /**
   * The account.
   */
  public function getAccount(): AccountInterface;

  /**
   * Sets the transfer method.
   */
  public function setTransferMethod(TransferMethodInterface $transfer_method): WithdrawInterface;

  /**
   * The transfer method entity.
   */
  public function getTransferMethod(): TransferMethodInterface;

}
