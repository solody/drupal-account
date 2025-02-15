<?php

namespace Drupal\account;

use Drupal\account\Entity\LedgerInterface;
use Drupal\account\Entity\WithdrawInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Session\AccountInterface;
use Drupal\account\Entity\Account;
use Drupal\account\Entity\AccountInterface as FinanceAccountInterface;
use Drupal\account\Entity\TransferMethod;

/**
 * Interface of FinanceManager.
 */
interface FinanceManagerInterface {

  /**
   * 创建账户，如果账户已存在，直接返回该账户.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user.
   * @param string $type
   *   The account type.
   * @param string $currency_code
   *   The currency of this account.
   *
   * @return \Drupal\account\Entity\AccountInterface
   *   The account.
   */
  public function createAccount(AccountInterface $user, string $type, string $currency_code): FinanceAccountInterface;

  /**
   * 获取一个账户.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user.
   * @param string $type
   *   The account type.
   *
   * @return \Drupal\account\Entity\AccountInterface|null
   *   The account.
   */
  public function getAccount(AccountInterface $user, string $type, string $currency_code): ?FinanceAccountInterface;

  /**
   * 增加记账记录.
   *
   * @param \Drupal\account\Entity\Account $financeAccount
   *   The account.
   * @param string $amountType
   *   Debit or credit.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   * @param string $remarks
   *   Remarks to this row.
   * @param \Drupal\Core\Entity\EntityInterface|null $source
   *   The source business entity.
   *
   * @return \Drupal\account\Entity\LedgerInterface
   *   The saved ledger.
   */
  public function createLedger(
    Account $financeAccount,
    string $amountType,
    Price $amount,
    string $remarks = '',
    ?EntityInterface $source = NULL,
  ): LedgerInterface;

  /**
   * 更新账户统计.
   *
   * @param \Drupal\account\Entity\AccountInterface $account
   *   The account.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateAccountStatistics(FinanceAccountInterface $account): void;

  /**
   * Get last ledger of the given account.
   *
   * @param \Drupal\account\Entity\Account $financeAccount
   *   The account.
   *
   * @return \Drupal\account\Entity\LedgerInterface|null
   *   The last ledger.
   */
  public function getLastLedger(Account $financeAccount): ?LedgerInterface;

  /**
   * 账户间转账.
   *
   * @param \Drupal\account\Entity\Account $form
   *   From which.
   * @param \Drupal\account\Entity\Account $to
   *   To which.
   * @param \Drupal\commerce_price\Price $amount
   *   How much.
   * @param string $message
   *   Remarks.
   * @param \Drupal\Core\Entity\EntityInterface|null $source
   *   The source business entity.
   */
  public function transfer(
    Account $form,
    Account $to,
    Price $amount,
    string $message = '',
    ?EntityInterface $source = NULL,
  ): void;

  /**
   * 统计账户正在处理的提现总额.
   *
   * @param \Drupal\account\Entity\AccountInterface $account
   *   Which account to count.
   *
   * @return \Drupal\commerce_price\Price
   *   The result.
   */
  public function countPendingWithdrawTotalAmount(FinanceAccountInterface $account): Price;

  /**
   * 统计账户已完成的提现总额.
   *
   * @param \Drupal\account\Entity\Account $account
   *   Which account to count.
   *
   * @return \Drupal\commerce_price\Price
   *   The result.
   */
  public function countCompleteWithdrawTotalAmount(FinanceAccountInterface $account): Price;

  /**
   * 计算账户的可用余额.
   *
   * @param \Drupal\account\Entity\Account $account
   *   Which account to compute.
   *
   * @return \Drupal\commerce_price\Price
   *   The result.
   */
  public function computeAvailableBalance(Account $account): Price;

  /**
   * Get all ledgers of the given account.
   *
   * @param \Drupal\account\Entity\Account $account
   *   Which account.
   *
   * @return \Drupal\account\Entity\LedgerInterface[]
   *   Entity of ledgers.
   */
  public function getLedgers(Account $account): array;

  /**
   * 申请提现.
   *
   * @param \Drupal\account\Entity\Account $account
   *   Which account.
   * @param \Drupal\commerce_price\Price $amount
   *   How much.
   * @param \Drupal\account\Entity\TransferMethod $transferMethod
   *   The transfer method.
   * @param string $remarks
   *   The remarks.
   *
   * @return \Drupal\account\Entity\WithdrawInterface
   *   The saved withdraw.
   */
  public function applyWithdraw(
    Account $account,
    Price $amount,
    TransferMethod $transferMethod,
    string $remarks = '',
  ): WithdrawInterface;

  /**
   * 检查是否有正在处理的提现单.
   *
   * @param \Drupal\account\Entity\AccountInterface $account
   *   The account to check.
   *
   * @return bool
   *   Yes or no.
   */
  public function hasProcessingWithdraw(FinanceAccountInterface $account): bool;

  /**
   * Get all accounts of the given type.
   *
   * @param string $type
   *   The type id.
   *
   * @return \Drupal\account\Entity\AccountInterface[]
   *   Accounts.
   */
  public function getAccountsByType(string $type): array;

}
