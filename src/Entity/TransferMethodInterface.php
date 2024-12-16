<?php

namespace Drupal\account\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Transfer method entities.
 *
 * @ingroup account
 */
interface TransferMethodInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Transfer method name.
   *
   * @return string
   *   Name of the Transfer method.
   */
  public function getName(): string;

  /**
   * Sets the Transfer method name.
   *
   * @param string $name
   *   The Transfer method name.
   *
   * @return \Drupal\account\Entity\TransferMethodInterface
   *   The called Transfer method entity.
   */
  public function setName(string $name): TransferMethodInterface;

  /**
   * Gets the Transfer method creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Transfer method.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Transfer method creation timestamp.
   *
   * @param int $timestamp
   *   The Transfer method creation timestamp.
   *
   * @return \Drupal\account\Entity\TransferMethodInterface
   *   The called Transfer method entity.
   */
  public function setCreatedTime(int $timestamp): TransferMethodInterface;

  /**
   * Sets gateway for the Transfer method.
   */
  public function setTransferGateway(TransferGatewayInterface $transfer_gateway): TransferMethodInterface;

  /**
   * The gateway.
   */
  public function getTransferGateway(): TransferGatewayInterface;

  /**
   * Indicated if it is default transfer method to use.
   */
  public function isDefault(): bool;

  /**
   * Set whether it is default or not.
   *
   * @param bool $value
   *   Whether it is default or not.
   */
  public function setDefault(bool $value): TransferMethodInterface;

}
