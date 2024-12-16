<?php

namespace Drupal\account\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ledger entities.
 */
class LedgerViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    // Additional information for Views integration, such as table joins, can be
    // put here.
    return parent::getViewsData();
  }

}
