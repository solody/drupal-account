<?php

namespace Drupal\account;

use Drupal\account\Entity\LedgerInterface;
use Drupal\account\Entity\WithdrawInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\account\Entity\Account;
use Drupal\account\Entity\AccountInterface as FinanceAccountInterface;
use Drupal\account\Entity\AccountType;
use Drupal\account\Entity\Ledger;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\account\Entity\TransferMethod;
use Drupal\account\Entity\Withdraw;

/**
 * The FinanceManager service.
 */
class FinanceManager implements FinanceManagerInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new FinanceManager object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount(AccountInterface $user, string $type): ?FinanceAccountInterface {
    $query = \Drupal::entityQuery('account')
      ->condition('uid', $user->id())
      ->condition('type', $type);
    $ids = $query->accessCheck(FALSE)->execute();

    if (!empty($ids)) {
      return Account::load(array_pop($ids));
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function createLedger(
    Account $financeAccount,
    string $amountType,
    Price $amount,
    string $remarks = '',
    ?EntityInterface $source = NULL,
  ): LedgerInterface {

    \Drupal::moduleHandler()->alter('account_ledger_remarks', $remarks, $source);

    // 使用操作锁，防止并发操作造成数据计算错误.
    $lock = \Drupal::lock();
    $operationID = 'finance__create_ledger__' . $financeAccount->id();
    $is_get_lock = $lock->acquire($operationID);
    if (!$is_get_lock) {
      if (!$lock->wait($operationID, 30)) {
        $is_get_lock = $lock->acquire($operationID);
      }
    }
    if ($is_get_lock) {

      try {

        // 计算余额.
        $balance = new Price('0.00', $financeAccount->getCurrencyCode());
        $last_ledger = $this->getLastLedger($financeAccount);
        if ($last_ledger) {
          $balance = $last_ledger->getBalance();
        }

        if ($amountType === Ledger::AMOUNT_TYPE_DEBIT) {
          $balance = $balance->add($amount);
        }
        elseif ($amountType === Ledger::AMOUNT_TYPE_CREDIT) {
          $balance = $balance->subtract($amount);
        }

        $create_data = [
          'account_id' => $financeAccount,
          'amount_type' => $amountType,
          'amount' => $amount,
          'balance' => $balance,
          'remarks' => $remarks,
        ];

        if ($source) {
          $create_data['source'] = $source;
        }

        $ledger = Ledger::create($create_data);
        $ledger->save();

        // 更新账户统计.
        $this->updateAccountStatistics($financeAccount);

        $lock->release($operationID);

        return $ledger;
      }
      catch (\Exception $exception) {
        $lock->release($operationID);
        throw $exception;
      }

    }
    else {
      throw new \Exception('未能取得操作[' . $operationID . ']的锁，无法执行记账操作。');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateAccountStatistics(FinanceAccountInterface $account): void {
    $ledgers = $this->getLedgers($account);
    $total_debit = new Price('0.00', $account->getCurrencyCode());
    $total_credit = new Price('0.00', $account->getCurrencyCode());

    foreach ($ledgers as $ledger) {
      /** @var \Drupal\account\Entity\Ledger $ledger */
      if ($ledger->getAmountType() === LedgerInterface::AMOUNT_TYPE_DEBIT) {
        $total_debit = $total_debit->add($ledger->getAmount());
      }
      elseif ($ledger->getAmountType() === LedgerInterface::AMOUNT_TYPE_CREDIT) {
        $total_credit = $total_credit->add($ledger->getAmount());
      }
    }

    $account->setTotalDebit($total_debit);
    $account->setTotalCredit($total_credit);

    // 计算余额.
    $balance = new Price('0.00', $account->getCurrencyCode());
    $last_ledger = $this->getLastLedger($account);
    if ($last_ledger) {
      $balance = $last_ledger->getBalance();
    }

    $account->setBalance($balance);

    $account->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastLedger(Account $financeAccount): ?LedgerInterface {
    $query = \Drupal::entityQuery('ledger')
      ->condition('account_id', $financeAccount->id())
      ->sort('id', 'DESC')
      ->range(0, 1);
    $ids = $query->accessCheck(FALSE)->execute();

    if (!empty($ids)) {
      return Ledger::load(array_pop($ids));
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createAccount(AccountInterface $user, string $type, string $currency_code): FinanceAccountInterface {
    $account = $this->getAccount($user, $type);

    if (!$account) {
      $account_type = AccountType::load($type);
      $price = new Price('0.00', $currency_code);

      $account = Account::create([
        'uid' => $user->id(),
        'type' => $type,
        'name' => $account_type->label(),
        'currency_code' => $currency_code,
        'total_debit' => $price,
        'total_credit' => $price,
        'balance' => $price,
      ]);

      $account->save();
    }

    return $account;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function transfer(
    Account $form,
    Account $to,
    Price $amount,
    string $message = '',
    ?EntityInterface $source = NULL,
  ): void {
    // 记录出账.
    $this->createLedger($form, LedgerInterface::AMOUNT_TYPE_CREDIT, $amount, $message, $source);
    // 记录进账.
    $this->createLedger($to, LedgerInterface::AMOUNT_TYPE_DEBIT, $amount, $message, $source);
  }

  /**
   * {@inheritdoc}
   */
  public function countPendingWithdrawTotalAmount(FinanceAccountInterface $account): Price {
    $query = \Drupal::entityQuery('withdraw')
      ->condition('state', ['draft', 'processing'], 'IN')
      ->condition('account_id', $account->id());
    $ids = $query->execute();

    $price = new Price('0.00', $account->getCurrencyCode());
    if (count($ids)) {
      $withdraws = Withdraw::loadMultiple($ids);

      foreach ($withdraws as $withdraw) {
        /** @var \Drupal\account\Entity\Withdraw $withdraw */
        $price = $price->add($withdraw->getAmount());
      }
    }

    return $price;
  }

  /**
   * {@inheritdoc}
   */
  public function countCompleteWithdrawTotalAmount(FinanceAccountInterface $account): Price {
    $query = \Drupal::entityQuery('withdraw')
      ->condition('state', 'completed')
      ->condition('account_id', $account->id());
    $ids = $query->execute();

    $price = new Price('0.00', $account->getCurrencyCode());
    if (count($ids)) {
      $withdraws = Withdraw::loadMultiple($ids);

      foreach ($withdraws as $withdraw) {
        /** @var \Drupal\account\Entity\Withdraw $withdraw */
        $price = $price->add($withdraw->getAmount());
      }
    }

    return $price;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function computeAvailableBalance(Account $account): Price {
    $amount = new Price('0.00', $account->getCurrencyCode());

    $ledgers = $this->getLedgers($account);
    $account_type = AccountType::load($account->bundle());

    $available_time = (new \DateTime())->sub(new \DateInterval('P' . (int) $account_type->getWithdrawPeriod() . 'D'));

    foreach ($ledgers as $ledger) {
      if ($ledger->getAmountType() === LedgerInterface::AMOUNT_TYPE_DEBIT) {
        if ($ledger->getCreatedTime() <= $available_time->getTimestamp()) {
          $amount = $amount->add($ledger->getAmount());
        }
      }
      elseif ($ledger->getAmountType() === LedgerInterface::AMOUNT_TYPE_CREDIT) {
        $amount = $amount->subtract($ledger->getAmount());
      }
    }

    return $amount;
  }

  /**
   * {@inheritdoc}
   */
  public function getLedgers(Account $account): array {
    $query = \Drupal::entityQuery('ledger')
      ->condition('account_id', $account->id());
    $ids = $query->accessCheck(FALSE)->execute();

    if (count($ids)) {
      return Ledger::loadMultiple($ids);
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function applyWithdraw(
    Account $account,
    Price $amount,
    TransferMethod $transferMethod,
    string $remarks = '',
  ): WithdrawInterface {
    // 检查提现限制.
    $account_type = AccountType::load($account->bundle());
    if ((boolean) $account_type->getMaximumWithdraw() && (float) $amount->getNumber() > (float) $account_type->getMaximumWithdraw()) {
      throw new \Exception('申请金额超过了最大限额');
    }
    if ((boolean) $account_type->getMinimumWithdraw() && (float) $amount->getNumber() < $account_type->getMinimumWithdraw()) {
      throw new \Exception('申请金额没有达到最小限额');
    }

    $available_balance = $this->computeAvailableBalance($account);
    if ($amount->greaterThan($available_balance)) {
      throw new \Exception('申请金额超过了可提余额');
    }

    // 检查是否有提现单正在处理.
    if ($this->hasProcessingWithdraw($account)) {
      throw new \Exception('您的账户已经有提现申请正在处理，请等待处理完毕，再申请新的提现。');
    }

    // 创建提现单.
    /** @var \Drupal\account\Entity\Withdraw $withdraw */
    $withdraw = Withdraw::create([
      'account_id' => $account,
      'amount' => $amount,
      'transfer_method' => $transferMethod,
      'state' => 'draft',
      'remarks' => $remarks,
      'name' => $account->getOwner()->getDisplayName() . ' 的 ' . $account->getName() . '的提现申请',
    ]);
    $withdraw->save();

    $this->createLedger(
      $withdraw->getAccount(),
      LedgerInterface::AMOUNT_TYPE_CREDIT,
      $withdraw->getAmount(),
      '提现单 [' . $withdraw->id() . '] 提现支出' . $withdraw->getAmount()->getCurrencyCode() . $withdraw->getAmount()->getNumber(),
      $withdraw
    );

    return $withdraw;
  }

  /**
   * {@inheritdoc}
   */
  public function hasProcessingWithdraw(FinanceAccountInterface $account): bool {
    $query = \Drupal::entityQuery('withdraw')
      ->condition('account_id', $account->id())
      ->condition('state', ['draft', 'processing'], 'IN');
    $ids = $query->execute();

    if (count($ids)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountsByType(string $type): array {
    $query = \Drupal::entityQuery('account')
      ->condition('type', $type);
    $ids = $query->execute();
    if ($ids) {
      return Account::loadMultiple($ids);
    }
    else {
      return [];
    }
  }

}
