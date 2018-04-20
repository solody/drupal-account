<?php

namespace Drupal\finance\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ledger entities.
 *
 * @ingroup finance
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
   * @return \Drupal\finance\Entity\LedgerInterface
   *   The called Ledger entity.
   */
  public function setCreatedTime($timestamp);

}