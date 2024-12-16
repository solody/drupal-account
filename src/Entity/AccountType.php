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
 *   admin_permission = "administer site configuration",
 *   bundle_of = "account",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/account/account_type/{account_type}",
 *     "add-form" = "/admin/account/account_type/add",
 *     "edit-form" = "/admin/account/account_type/{account_type}/edit",
 *     "delete-form" = "/admin/account/account_type/{account_type}/delete",
 *     "collection" = "/admin/account/account_type"
 *   }
 * )
 */
class AccountType extends ConfigEntityBundleBase implements AccountTypeInterface {

  /**
   * The Account type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Account type label.
   *
   * @var string
   */
  protected $label;

  /**
   * 提现周期（天）
   *
   * @var int
   */
  protected $withdraw_period;

  /**
   * 最小单笔提现限额
   *
   * @var float
   */
  protected $minimum_withdraw;

  /**
   * 最大单笔提现限额
   *
   * @var float
   */
  protected $maximum_withdraw;

  /**
   *
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   *
   */
  public function getWithdrawPeriod() {
    return $this->withdraw_period;
  }

  /**
   *
   */
  public function getMinimumWithdraw() {
    return $this->minimum_withdraw;
  }

  /**
   *
   */
  public function getMaximumWithdraw() {
    return $this->maximum_withdraw;
  }

}
