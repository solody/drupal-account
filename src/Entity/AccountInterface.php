<?php

namespace Drupal\account\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Account entities.
 *
 * @ingroup account
 */
interface AccountInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Account name.
   *
   * @return string
   *   Name of the Account.
   */
  public function getName(): string;

  /**
   * Sets the Account name.
   *
   * @param string $name
   *   The Account name.
   *
   * @return \Drupal\account\Entity\AccountInterface
   *   The called Account entity.
   */
  public function setName(string $name): AccountInterface;

  /**
   * Gets the currency code of this account.
   *
   * @return string
   *   Currency code of the Account.
   */
  public function getCurrencyCode(): string;

  /**
   * Gets the Account creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Account.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Account creation timestamp.
   *
   * @param int $timestamp
   *   The Account creation timestamp.
   *
   * @return \Drupal\account\Entity\AccountInterface
   *   The called Account entity.
   */
  public function setCreatedTime(int $timestamp): AccountInterface;

  /**
   * Get the balance of this account.
   *
   * @return \Drupal\commerce_price\Price
   *   The balance amount.
   */
  public function getBalance(): Price;

  /**
   * Set the balance amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to set.
   *
   * @return \Drupal\account\Entity\AccountInterface
   *   The called Account entity.
   */
  public function setBalance(Price $amount): AccountInterface;

  /**
   * Get the total credit amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The amount.
   */
  public function getTotalCredit(): Price;

  /**
   * Set the total credit amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   *
   * @return \Drupal\account\Entity\AccountInterface
   *   The called Account entity.
   */
  public function setTotalCredit(Price $amount): AccountInterface;

  /**
   * Get the total debit amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The amount.
   */
  public function getTotalDebit(): Price;

  /**
   * Set the total debit amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   *
   * @return \Drupal\account\Entity\AccountInterface
   *   The called Account entity.
   */
  public function setTotalDebit(Price $amount): AccountInterface;

}
