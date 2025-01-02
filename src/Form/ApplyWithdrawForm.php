<?php

namespace Drupal\account\Form;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\account\Entity\Account;
use Drupal\account\Entity\AccountType;
use Drupal\account\Entity\TransferMethod;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\account\FinanceManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ApplyWithdrawForm.
 */
class ApplyWithdrawForm extends FormBase {

  /**
   * Drupal\account\FinanceManagerInterface definition.
   *
   * @var \Drupal\account\FinanceManagerInterface
   */
  protected $accountFinanceManager;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a new ApplyWithdrawForm object.
   */
  public function __construct(
    FinanceManagerInterface $account_finance_manager,
    CurrencyFormatterInterface $currency_formatter
  ) {
    $this->accountFinanceManager = $account_finance_manager;
    $this->currencyFormatter = $currency_formatter;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('account.finance_manager'),
      $container->get('commerce_price.currency_formatter')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'account_apply_withdraw_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $account_id = $this->getRouteMatch()->getParameter('account');
    $account = Account::load($account_id);

    if ($account->getOwnerId() !== $this->currentUser()->id()) {
      throw new AccessDeniedHttpException('您无权访问此账户');
    }

    $account_type = AccountType::load($account->bundle());
    $available_balance = $this->accountFinanceManager->computeAvailableBalance($account);
    $withdraw_limitation = '没有限制';
    if ($account_type->getMinimumWithdraw() || $account_type->getMaximumWithdraw()) {
      $withdraw_limitation = '';
      if ($account_type->getMinimumWithdraw()) {
        $price = $this->currencyFormatter->format((string)$account_type->getMinimumWithdraw(), $account->getCurrencyCode());
        $withdraw_limitation .= '<div>最低限制：'.$price.'</div>';
      }
      if ($account_type->getMaximumWithdraw()) {
        $price = $this->currencyFormatter->format((string)$account_type->getMaximumWithdraw(), $account->getCurrencyCode());
        $withdraw_limitation .= '<div>最高限制：'.$price.'</div>';
      }
    }

    // 显示账户摘要：余额，可提余额，提现限制
    $form['account_summary'] = [
      '#type' => 'table',
      '#caption' => $account->getName(),
      '#header' => [
        $this->t('余额'),
        $this->t('可提余额'),
        $this->t('提现限制')
      ]
    ];
    $form['account_summary'][] = [
      ['#markup' => $this->currencyFormatter->format($account->getBalance()->getNumber(), $account->getBalance()->getCurrencyCode())],
      ['#markup' => $this->currencyFormatter->format($available_balance->getNumber(), $available_balance->getCurrencyCode())],
      ['#markup' => $withdraw_limitation]
    ];

    $form['withdraw'] = [
      '#type' => 'fieldset',
      '#tree' => true,
      '#title' => $this->t('申请提现')
    ];

    // 输入要提现的金额
    $form['withdraw']['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('输入要提现的金额'),
      '#default_value' => ['number' => '100.00', 'currency_code' => $account->getCurrencyCode()],
      '#allow_negative' => FALSE,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#available_currencies' => [$account->getCurrencyCode()],
    ];

    // 选择提现方式
    $transfer_methods = \Drupal::entityTypeManager()
      ->getStorage('account_transfer_method')
      ->loadByProperties(['uid' => $this->currentUser()->id()]);

    $transfer_method_options = [];
    $default_transfer_method = null;
    foreach ($transfer_methods as $transfer_method) {
      if ($transfer_method instanceof TransferMethod) {
        if (!$default_transfer_method) $default_transfer_method = $transfer_method->id();
        $transfer_method_options[$transfer_method->id()] = $transfer_method->getName();
      }
    }

    $form['withdraw']['transfer_method'] = [
      '#type' => 'select',
      '#title' => $this->t('提现方式'),
      '#default_value' => $default_transfer_method,
      '#options' => $transfer_method_options,
      '#required' => TRUE
    ];

    $form['withdraw']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // 检查金额
    $account_id = $this->getRouteMatch()->getParameter('account');
    $account = Account::load($account_id);
    $account_type = AccountType::load($account->bundle());
    $available_balance = $this->accountFinanceManager->computeAvailableBalance($account);

    $amount = $form_state->getValue('withdraw')['amount'];
    $amount_price = new Price($amount['number'], $amount['currency_code']);
    if ($amount_price->greaterThan($available_balance)) {
      $form_state->setError($form['withdraw']['amount'], '超过了可提现金额');
    }
    if ($account_type->getMinimumWithdraw() &&
      (float)$amount_price->getNumber() < (float)$account_type->getMinimumWithdraw()) {
      $form_state->setError($form['withdraw']['amount'], '没到达到最小提现限额（'.$this->currencyFormatter->format($account_type->getMinimumWithdraw(), $account->getCurrencyCode()).'）');
    }
    if ($account_type->getMaximumWithdraw() &&
      (float)$amount_price->getNumber() > (float)$account_type->getMaximumWithdraw()) {
      $form_state->setError($form['withdraw']['amount'], '超过了最大提现限额（'.$this->currencyFormatter->format($account_type->getMaximumWithdraw(), $account->getCurrencyCode()).'）');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // 创建提现单
    $transfer_method = TransferMethod::load($form_state->getValue('withdraw')['transfer_method']);
    if (!$transfer_method) throw new BadRequestHttpException('找不到支付方法：【'.$form_state->getValue('withdraw')['transfer_method'].'】');

    try {
      $account_id = $this->getRouteMatch()->getParameter('account');
      $account = Account::load($account_id);
      $amount = $form_state->getValue('withdraw')['amount'];
      $amount_price = new Price($amount['number'], $amount['currency_code']);

      $withdraw = $this->accountFinanceManager->applyWithdraw($account, $amount_price, $transfer_method, '商家提现');

      \Drupal::messenger()->addMessage('提现申请成功！');
    } catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

}
