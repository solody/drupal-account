<?php

namespace Drupal\account;

use Drupal\commerce_price\Price;
use Drupal\Core\Session\AccountInterface;
use Drupal\account\Entity\Account;
use Drupal\account\Entity\Ledger;
use Drupal\account\Entity\TransferMethod;
use Drupal\account\Entity\Withdraw;
use Drupal\user\Entity\User;

/**
 * Interface FinanceManagerInterface.
 */
interface FinanceManagerInterface {

    /**
     * 创建账户，如果账户已存在，直接返回该账户
     *
     * @param User $user
     * @param $type
     * @return Account
     */
    public function createAccount(AccountInterface $user, string $type, string $currency_code);

    /**
     * 获取一个账户
     *
     * @param User $user
     * @param $type
     * @return Account
     */
    public function getAccount(AccountInterface $user, $type);

    /**
     * 增加记账记录
     *
     * @param Account $financeAccount
     * @param $amountType
     * @param $amount
     * @param $remarks
     * @param $source
     * @return Ledger
     */
    public function createLedger(Account $financeAccount,
                                 $amountType,
                                 Price $amount,
                                 $remarks = '',
                                 $source = null);

    /**
     * 账户间转账
     *
     * @param Account $form
     * @param Account $to
     * @param Price $amount
     * @param string $message
     * @param null $source
     * @return mixed
     */
    public function transfer(Account $form, Account $to, Price $amount, $message = '', $source = null);

    /**
     * 统计账户正在处理的提现总额
     *
     * @param Account $account
     * @return Price
     */
    public function countPendingWithdrawTotalAmount(Account $account);

    /**
     * 统计账户已完成的提现总额
     *
     * @param Account $account
     * @return Price
     */
    public function countCompleteWithdrawTotalAmount(Account $account);

    /**
     * 计算账户的可用余额
     *
     * @param Account $account
     * @return Price
     */
    public function computeAvailableBalance(Account $account);


    /**
     * @param Account $account
     * @return Ledger[]
     */
    public function getLedgers(Account $account);

    /**
     * 申请提现
     *
     * @param Account $account
     * @param Price $amount
     * @param TransferMethod $transferMethod
     * @param string $remarks
     * @return Withdraw
     * @throws \Exception
     */
    public function applyWithdraw(Account $account, Price $amount, TransferMethod $transferMethod, $remarks = '');


    /**
     * 检查是否有正在处理的提现单
     *
     * @param Account $account
     * @return bool
     */
    public function hasProcessingWithdraw(Account $account);

    /**
     * @param $type
     * @return Account[]
     */
    public function getAccountsByType($type);
}
