<?php

namespace Drupal\account\Plugin\TransferGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\account\Entity\WithdrawInterface;
use Drupal\account\Plugin\TransferGatewayBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * @TransferGateway(
 *   id = "manual",
 *   label = @Translation("Manual")
 * )
 */
class Manual extends TransferGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {

    $fields['manual_remarks'] = BundleFieldDefinition::create('string')
      ->setLabel(t('手动转帐方法说明'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * 转账
   *
   * @param \Drupal\account\Entity\WithdrawInterface $withdraw
   *
   * @return bool
   */
  public function transfer(WithdrawInterface $withdraw) {
    return TRUE;
  }

}
