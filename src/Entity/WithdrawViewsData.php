<?php

namespace Drupal\account\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Withdraw entities.
 */
class WithdrawViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
