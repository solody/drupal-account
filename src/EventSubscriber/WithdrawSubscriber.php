<?php

namespace Drupal\account\EventSubscriber;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\account\Entity\LedgerInterface;
use Drupal\account\Entity\TransferGatewayInterface as TransferGatewayEntityInterface;
use Drupal\account\Plugin\TransferGatewayInterface;
use Drupal\account\Entity\TransferMethodInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\account\FinanceManagerInterface;

/**
 * Actions for the withdraw entity state machine change.
 */
class WithdrawSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new WithdrawSubscriber object.
   */
  public function __construct(
    private readonly FinanceManagerInterface $accountFinanceManager,
    private readonly CurrencyFormatterInterface $currencyFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['withdraw.transfer.pre_transition'] = ['withdrawTransferPreTransition'];
    $events['withdraw.retry.pre_transition'] = ['withdrawTransferPreTransition'];
    $events['withdraw.cancel.post_transition'] = ['withdrawCancelPostTransition'];

    return $events;
  }

  /**
   * Action to transfer.
   *
   * This method is called whenever the withdraw.transfer.pre_transition
   * event is dispatched.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   *
   * @throws \Exception
   */
  public function withdrawTransferPreTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\account\Entity\Withdraw $withdraw */
    $withdraw = $event->getEntity();

    $transfer_method = $withdraw->getTransferMethod();
    if ($transfer_method instanceof TransferMethodInterface) {
      $gateway = $transfer_method->getTransferGateway();
      if ($gateway instanceof TransferGatewayEntityInterface) {
        $plugin = $gateway->getPlugin();
        if ($plugin instanceof TransferGatewayInterface) {
          try {
            $plugin->transfer($withdraw);
            \Drupal::messenger()->addMessage(
              $this->t('Withdraw @withdraw transfer successful by gateway @gateway.', [
                '@withdraw' => $withdraw->id(),
                '@gateway' => $plugin->getPluginId(),
              ])
            );
          }
          catch (\Exception $exception) {
            \Drupal::messenger()->addError(
              $this->t('Withdraw @withdraw transfer fails by gateway @gateway: @message', [
                '@withdraw' => $withdraw->id(),
                '@gateway' => $plugin->getPluginId(),
                '@message' => $exception->getMessage(),
              ])
            );
          }
        }
      }
    }
  }

  /**
   * Action after canceled the withdraw, refund amount to account.
   *
   * This method is called whenever the withdraw.cancel.post_transition
   * event is dispatched.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function withdrawCancelPostTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\account\Entity\Withdraw $withdraw */
    $withdraw = $event->getEntity();

    $this->accountFinanceManager->createLedger(
      $withdraw->getAccount(),
      LedgerInterface::AMOUNT_TYPE_DEBIT,
      $withdraw->getAmount(),
      $this->t('Withdraw @withdraw is rejected, refund amount @amount.', [
        '@withdraw' => $withdraw->id(),
        '@amount' => $this->currencyFormatter->format(
          $withdraw->getAmount()->getNumber(),
          $withdraw->getAmount()->getCurrencyCode()
        ),
      ]),
      $withdraw
    );
  }

}
