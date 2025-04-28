<?php

namespace Drupal\account\Form;

use Drupal\account\Entity\Account;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\account\FinanceManagerInterface;

/**
 * Form to manually adjust balance to an account.
 */
class ManualAddAccountLedgerForm extends FormBase {

  /**
   * Drupal\account\FinanceManagerInterface definition.
   *
   * @var \Drupal\account\FinanceManagerInterface
   */
  protected $accountFinanceManager;

  /**
   * Constructs a new ManualAddAccountLedgerForm object.
   */
  public function __construct(
    FinanceManagerInterface $account_finance_manager,
  ) {
    $this->accountFinanceManager = $account_finance_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('account.finance_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manual_add_account_ledger_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('Amount to adjust.'),
      '#weight' => '0',
    ];
    $form['amount_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Amount Type'),
      '#options' => ['credit' => $this->t('Credit'), 'debit' => $this->t('Debit')],
      '#default_value' => 'debit',
      '#weight' => '0',
    ];
    $account_id = \Drupal::routeMatch()->getParameter('account_id');
    $account = NULL;
    if ($account_id) {
      $account = Account::load($account_id);
    }
    $form['account'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Finance Account'),
      '#target_type' => 'account',
      '#default_value' => $account,
      '#disabled' => !empty($account),
      '#weight' => '0',
    ];

    $form['remarks'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Remarks for this ledger.'),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // \Drupal::messenger()->addMessage('');
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\account\FinanceManagerInterface $finance_manager */
    $finance_manager = \Drupal::service('account.finance_manager');
    $values = $form_state->getValues();
    $account = Account::load($values['account']);
    $finance_manager->createLedger($account, $values['amount_type'], new Price($values['amount']['number'], $values['amount']['currency_code']), $values['remarks']);
    $this->messenger()->addStatus('已成功调整了账户余额！');
    $form_state->setRedirect('entity.account.canonical', ['account' => $account->id()]);
  }

}
