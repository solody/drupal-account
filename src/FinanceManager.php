<?php

namespace Drupal\account;

use Drupal\account\Entity\LedgerInterface;
use Drupal\account\Entity\WithdrawInterface;
use Drupal\account\Plugin\TransferGatewayInterface;
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
use Drupal\account\Entity\TransferGatewayInterface as TransferGatewayEntityInterface;
use Drupal\account\Entity\TransferMethodInterface;
use Drupal\lock_utils\LockOperationTrait;

/**
 * The FinanceManager service.
 */
class FinanceManager implements FinanceManagerInterface {

  use LockOperationTrait;

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
  public function getAccount(AccountInterface $user, string $type, string $currency_code): ?FinanceAccountInterface {
    $lock = \Drupal::lock();
    $operationID = 'finance__get_account';
    $is_get_lock = $lock->acquire($operationID);
    if (!$is_get_lock) {
      if (!$lock->wait($operationID, 30)) {
        $is_get_lock = $lock->acquire($operationID);
      }
    }
    if ($is_get_lock) {
      try {
        $query = \Drupal::entityQuery('account')
          ->condition('uid', $user->id())
          ->condition('type', $type)
          ->condition('currency', $currency_code);
        $ids = $query->accessCheck(FALSE)->execute();

        if (!empty($ids)) {
          return Account::load(array_pop($ids));
        }
        else {
          return $this->createAccount($user, $type, $currency_code);
        }
      }
      finally {
        $lock->release($operationID);
      }
    }
    else {
      throw new \Exception('Can not acquire lock [' . $operationID . ']');
    }

  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createAccount(AccountInterface $user, string $type, string $currency_code): FinanceAccountInterface {
    $account_type = AccountType::load($type);
    $price = new Price('0.00', $currency_code);

    $account = Account::create([
      'uid' => $user->id(),
      'type' => $type,
      'name' => $account_type->label(),
      'currency' => $currency_code,
      'total_debit' => $price,
      'total_credit' => $price,
      'balance' => $price,
    ]);

    $account->save();

    return $account;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function createLedger(
    Account $finance_account,
    string $amount_ype,
    Price $amount,
    string $remarks = '',
    ?EntityInterface $source = NULL,
  ): LedgerInterface {

    \Drupal::moduleHandler()->alter('account_ledger_remarks', $remarks, $source);

    $operationID = 'finance__create_ledger__' . $finance_account->id();
    return $this->lockDo($operationID, function () use ($finance_account, $amount_ype, $amount, $remarks, $source) {
      // 计算余额.
      $balance = new Price('0.00', $finance_account->getCurrency());
      $last_ledger = $this->getLastLedger($finance_account);
      if ($last_ledger) {
        $balance = $last_ledger->getBalance();
      }

      if ($amount_ype === LedgerInterface::AMOUNT_TYPE_DEBIT) {
        $balance = $balance->add($amount);
      }
      elseif ($amount_ype === LedgerInterface::AMOUNT_TYPE_CREDIT) {
        $balance = $balance->subtract($amount);
      }

      $create_data = [
        'account_id' => $finance_account,
        'amount_type' => $amount_ype,
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
      $this->updateAccountStatistics($finance_account);

      return $ledger;
    });

  }

  /**
   * {@inheritdoc}
   */
  public function updateAccountStatistics(FinanceAccountInterface $account): void {
    $ledgers = $this->getLedgers($account);
    $total_debit = new Price('0.00', $account->getCurrency());
    $total_credit = new Price('0.00', $account->getCurrency());

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
    $balance = new Price('0.00', $account->getCurrency());
    $last_ledger = $this->getLastLedger($account);
    if ($last_ledger) {
      $balance = $last_ledger->getBalance();
    }

    $account->setBalance($balance);

    $account->save();
  }

  /**
   * Get account total income.
   */
  public function getAccountTotal(FinanceAccountInterface $account, array $conditions = [], ?int $start_time = NULL, ?int $end_time = NULL): array {
    $ledgers = $this->getLedgers($account, $conditions, $start_time, $end_time);
    $total_debit = new Price('0.00', $account->getCurrency());
    $total_credit = new Price('0.00', $account->getCurrency());
    foreach ($ledgers as $ledger) {
      /** @var \Drupal\account\Entity\Ledger $ledger */
      if ($ledger->getAmountType() === LedgerInterface::AMOUNT_TYPE_DEBIT) {
        $total_debit = $total_debit->add($ledger->getAmount());
      }
      elseif ($ledger->getAmountType() === LedgerInterface::AMOUNT_TYPE_CREDIT) {
        $total_credit = $total_credit->add($ledger->getAmount());
      }
    }
    return [
      'debit' => $total_debit,
      'credit' => $total_credit,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLastLedger(Account $financeAccount): ?LedgerInterface {
    $query = \Drupal::entityQuery('ledger')
      ->condition('account_id', $financeAccount->id())
      ->sort('ledger_id', 'DESC')
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
  public function transfer(
    Account $form,
    Account $to,
    Price $amount,
    string $message = '',
    ?EntityInterface $source = NULL,
  ): void {
    $this->createLedger($form, LedgerInterface::AMOUNT_TYPE_CREDIT, $amount, $message, $source);
    $this->createLedger($to, LedgerInterface::AMOUNT_TYPE_DEBIT, $amount, $message, $source);
  }

  /**
   * {@inheritdoc}
   */
  public function countPendingWithdrawTotalAmount(FinanceAccountInterface $account): Price {
    $query = \Drupal::entityQuery('withdraw')
      ->condition('state', ['draft', 'processing'], 'IN')
      ->condition('account_id', $account->id());
    $ids = $query->accessCheck(FALSE)->execute();

    $price = new Price('0.00', $account->getCurrency());
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
    $ids = $query->accessCheck(FALSE)->execute();

    $price = new Price('0.00', $account->getCurrency());
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
    $amount = new Price('0.00', $account->getCurrency());

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
  public function getLedgers(Account $account, array $conditions = [], ?int $start_time = NULL, ?int $end_time = NULL): array {
    $query = \Drupal::entityQuery('ledger')
      ->condition('account_id', $account->id());
    foreach ($conditions as $key => $condition) {
      if (is_array($condition)) {
        $query->condition($key, $condition[0], $condition[1]);
      }
      else {
        $query->condition($key, $condition);
      }
    }
    if ($start_time) {
      $query->condition('created', $start_time, '>=');
    }
    if ($end_time) {
      $query->condition('created', $end_time, '<');
    }
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
    TransferMethod $transfer_method,
    string $remarks = '',
  ): WithdrawInterface {

    $operation_id = 'finance__apply_withdraw__' . $account->id();
    return $this->lockDo($operation_id, function () use ($account, $amount, $transfer_method, $remarks) {
      // Check if the amount is valid.
      $account_type = AccountType::load($account->bundle());
      if ($account_type->getMaximumWithdraw() > 0 && (float) $amount->getNumber() > $account_type->getMaximumWithdraw()) {
        throw new \Exception('Apply amount exceeds maximum limit');
      }
      if ($account_type->getMinimumWithdraw() > 0 && (float) $amount->getNumber() < $account_type->getMinimumWithdraw()) {
        throw new \Exception('Apply amount is less than minimum limit');
      }

      $available_balance = $this->computeAvailableBalance($account);
      if ($amount->greaterThan($available_balance)) {
        throw new \Exception('Available balance is not enough to withdraw.');
      }

      // Withdraw must be applied one by one.
      if ($this->hasProcessingWithdraw($account)) {
        throw new \Exception('There is a processing withdraw, cannot be applied again before it is done.');
      }

      // Create a new withdraw.
      /** @var \Drupal\account\Entity\WithdrawInterface $withdraw */
      $withdraw = Withdraw::create([
        'account_id' => $account,
        'amount' => $amount,
        'transfer_method' => $transfer_method,
        'state' => 'draft',
        'remarks' => $remarks,
        'name' => "Withdraw of {$account->getName()} of {$account->getOwner()->getDisplayName()}",
      ]);
      $withdraw->save();

      return $withdraw;
    });

  }

  /**
   * {@inheritdoc}
   */
  public function executeWithdraw(WithdrawInterface $withdraw): void {
    $operation_id = 'finance__execute_withdraw__' . $withdraw->id();
    $this->lockDo($operation_id, function () use ($withdraw) {
      $available_balance = $this->computeAvailableBalance($withdraw->getAccount());
      if ($available_balance->lessThan($withdraw->getAmount())) {
        throw new \Exception('Available balance is not enough to withdraw.');
      }
      $transfer_method = $withdraw->getTransferMethod();
      if ($transfer_method instanceof TransferMethodInterface) {
        $gateway = $transfer_method->getTransferGateway();
        if ($gateway instanceof TransferGatewayEntityInterface) {
          $plugin = $gateway->getPlugin();
          if ($plugin instanceof TransferGatewayInterface) {
            $plugin->transfer($withdraw);
            $this->createLedger(
              $withdraw->getAccount(),
              LedgerInterface::AMOUNT_TYPE_CREDIT,
              $withdraw->getAmount(),
              'Withdraw',
              $withdraw
            );
          }
        }
      }
    });
  }

  /**
   * {@inheritdoc}
   */
  public function hasProcessingWithdraw(FinanceAccountInterface $account): bool {
    $query = \Drupal::entityQuery('withdraw')
      ->condition('account_id', $account->id())
      ->condition('state', ['draft', 'processing'], 'IN');
    $ids = $query->accessCheck(FALSE)->execute();

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
    $ids = $query->accessCheck(FALSE)->execute();
    if ($ids) {
      return Account::loadMultiple($ids);
    }
    else {
      return [];
    }
  }

}
