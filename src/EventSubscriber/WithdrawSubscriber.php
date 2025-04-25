<?php

namespace Drupal\account\EventSubscriber;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
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
    $events['withdraw.transfer.post_transition'] = ['withdrawTransferPostTransition'];
    $events['withdraw.retry.post_transition'] = ['withdrawTransferPostTransition'];

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
  public function withdrawTransferPreTransition(WorkflowTransitionEvent $event): void {
    /** @var \Drupal\account\Entity\Withdraw $withdraw */
    $withdraw = $event->getEntity();
    $transfer_method = $withdraw->getTransferMethod();
    $gateway = $transfer_method->getTransferGateway();
    $plugin = $gateway->getPlugin();
    try {
      $this->accountFinanceManager->executeWithdraw($withdraw);
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
      // Prevent the transition.
      throw $exception;
    }
  }

  /**
   * Action after transfer the withdraw.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function withdrawTransferPostTransition(WorkflowTransitionEvent $event): void {
    /** @var \Drupal\account\Entity\Withdraw $withdraw */
    $withdraw = $event->getEntity();
    $withdraw->getState()->applyTransitionById('complete');
    $withdraw->save();
  }

}
