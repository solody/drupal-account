<?php

namespace Drupal\account\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default form for edit AccountType entity.
 */
class AccountTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\account\Entity\AccountType $account_type */
    $account_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $account_type->label(),
      '#description' => $this->t("Label for the Account type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $account_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\account\Entity\AccountType::load',
      ],
      '#disabled' => !$account_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    $form['withdraw_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Withdraw period(days)'),
      '#min' => 0,
      '#default_value' => $account_type->getWithdrawPeriod(),
    ];

    $form['minimum_withdraw'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum withdraw limitation'),
      '#min' => 0,
      '#step' => 0.01,
      '#default_value' => $account_type->getMinimumWithdraw(),
    ];

    $form['maximum_withdraw'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum withdraw limitation'),
      '#min' => 0,
      '#step' => 0.01,
      '#default_value' => $account_type->getMaximumWithdraw(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $account_type = $this->entity;
    $status = $account_type->save();

    switch ($status) {
      case SAVED_NEW:
        ($this->t('Created the %label Account type.', [
          '%label' => $account_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Account type.', [
          '%label' => $account_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($account_type->toUrl('collection'));
  }

}
