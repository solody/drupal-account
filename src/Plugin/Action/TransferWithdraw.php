<?php

namespace Drupal\account\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Publishes a product.
 *
 * @Action(
 *   id = "account_transfer_withdraw",
 *   label = @Translation("Transfer withdraw"),
 *   type = "withdraw"
 * )
 */
class TransferWithdraw extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\account\Entity\Withdraw $entity */

    /** @var \Drupal\distribution\DistributionManager $distribution_manager */
    $distribution_manager = \Drupal::getContainer()->get('distribution.distribution_manager');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\account\Entity\Withdraw $object */
    $result = $object
      ->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
