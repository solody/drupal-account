<?php

namespace Drupal\account\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Account type entity.
 *
 * @ConfigEntityType(
 *   id = "account_type",
 *   label = @Translation("Account type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\account\AccountTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\account\Form\AccountTypeForm",
 *       "edit" = "Drupal\account\Form\AccountTypeForm",
 *       "delete" = "Drupal\account\Form\AccountTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\account\AccountTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "account_type",
 *   admin_permission = "administer account types",
 *   bundle_of = "account",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "withdraw_period",
 *     "minimum_withdraw",
 *     "maximum_withdraw",
 *   },
 *   links = {
 *     "canonical" = "/admin/finance/account_type/{account_type}",
 *     "add-form" = "/admin/finance/account_type/add",
 *     "edit-form" = "/admin/finance/account_type/{account_type}/edit",
 *     "delete-form" = "/admin/finance/account_type/{account_type}/delete",
 *     "collection" = "/admin/finance/account_type"
 *   }
 * )
 */
class AccountType extends ConfigEntityBundleBase implements AccountTypeInterface {

  /**
   * The Account type ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The Account type label.
   *
   * @var string
   */
  protected string $label;

  /**
   * 提现周期（天）.
   *
   * @var int
   */
  protected int $withdraw_period = 0;

  /**
   * 最小单笔提现限额.
   *
   * @var float
   */
  protected float $minimum_withdraw = 0.0;

  /**
   * 最大单笔提现限额.
   *
   * @var float
   */
  protected float $maximum_withdraw = 0.0;

  /**
   * {@inheritdoc}
   */
  public function getWithdrawPeriod(): int {
    return $this->withdraw_period;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinimumWithdraw(): float {
    return $this->minimum_withdraw;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaximumWithdraw(): float {
    return $this->maximum_withdraw;
  }

}
