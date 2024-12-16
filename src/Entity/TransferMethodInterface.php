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

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Transfer method name.
   *
   * @return string
   *   Name of the Transfer method.
   */
  public function getName();

  /**
   * Sets the Transfer method name.
   *
   * @param string $name
   *   The Transfer method name.
   *
   * @return \Drupal\account\Entity\TransferMethodInterface
   *   The called Transfer method entity.
   */
  public function setName($name);

  /**
   * Gets the Transfer method creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Transfer method.
   */
  public function getCreatedTime();

  /**
   * Sets the Transfer method creation timestamp.
   *
   * @param int $timestamp
   *   The Transfer method creation timestamp.
   *
   * @return \Drupal\account\Entity\TransferMethodInterface
   *   The called Transfer method entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * @param TransferGatewayInterface $transfer_gateway
   * @return $this
   */
  public function setTransferGateway(TransferGatewayInterface $transfer_gateway);

  /**
   * @return TransferGatewayInterface
   */
  public function getTransferGateway();

  /**
   * @return bool
   */
  public function isDefault();

  /**
   * @param bool $value
   * @return $this
   */
  public function setDefault($value);

}
