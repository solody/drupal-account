<?php

namespace Drupal\account\EventSubscriber;

use Drupal\account\Entity\Ledger;
use Drupal\account\Entity\TransferGatewayInterface;
use Drupal\account\Entity\TransferMethodInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\account\FinanceManagerInterface;

/**
 * Actions for the withdraw entity state machine change.
 */
class WithdrawSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\account\FinanceManagerInterface definition.
   *
   * @var \Drupal\account\FinanceManagerInterface
   */
  protected FinanceManagerInterface $accountFinanceManager;

  /**
   * Constructs a new WithdrawSubscriber object.
   */
  public function __construct(FinanceManagerInterface $account_finance_manager) {
    $this->accountFinanceManager = $account_finance_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['withdraw.transfer.pre_transition'] = ['withdrawTransferPreTransition'];
    $events['withdraw.transfer.post_transition'] = ['withdrawTransferPostTransition'];
    $events['withdraw.cancel.post_transition'] = ['withdrawCancelPostTransition'];

    return $events;
  }

  /**
   * Action to transfer.
   *
   * This method is called whenever the withdraw.transfer.pre_transition
   * event is dispatched.
   *
   * 状态切换之前，执行转账插件
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function withdrawTransferPreTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\account\Entity\Withdraw $withdraw */
    $withdraw = $event->getEntity();

    $transfer_method = $withdraw->getTransferMethod();
    if ($transfer_method instanceof TransferMethodInterface) {
      $gateway = $transfer_method->getTransferGateway();
      if ($gateway instanceof TransferGatewayInterface) {
        $plugin = $gateway->getPlugin();
        if ($plugin instanceof TransferGatewayInterface) {
          try {
            $plugin->transfer($withdraw);
            \Drupal::messenger()->addMessage('提现单[' . $withdraw->id() . ']状态已切换为[已完成]，' . $plugin->getPluginId() . '转帐打款请求成功：');
          }
          catch (\Exception $exception) {
            \Drupal::messenger()->addError('提现单[' . $withdraw->id() . ']打款失败：' . $plugin->getPluginId() . ':' . $exception->getMessage());
            // 跳转以终止状态转换.
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
          }
        }
      }
    }
  }

  /**
   * Action after transfer.
   *
   * This method is called whenever the withdraw.transfer.post_transition
   * event is dispatched.
   *
   * @todo 扣取提现手续费
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function withdrawTransferPostTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\account\Entity\Withdraw $withdraw */
    $withdraw = $event->getEntity();
  }

  /**
   * Action after canceled the withdraw.
   *
   * This method is called whenever the withdraw.cancel.post_transition
   * event is dispatched.
   *
   * 退款到账户余额
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function withdrawCancelPostTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\account\Entity\Withdraw $withdraw */
    $withdraw = $event->getEntity();

    $this->accountFinanceManager->createLedger(
      $withdraw->getAccount(),
      Ledger::AMOUNT_TYPE_DEBIT,
      $withdraw->getAmount(),
      '提现单 [' . $withdraw->id() . '] 被拒绝，金额退回' . $withdraw->getAmount()->getCurrencyCode() . $withdraw->getAmount()->getNumber(),
      $withdraw
    );
  }

}
