<?php

namespace Drupal\account\Plugin\TransferGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\account\Entity\WithdrawInterface;
use Drupal\account\Plugin\TransferGatewayBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity\BundleFieldDefinition;

/**
 * The default manual plugin.
 *
 * @TransferGateway(
 *   id = "manual",
 *   label = @Translation("Manual")
 * )
 */
class Manual extends TransferGatewayBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {

    $fields['manual_remarks'] = BundleFieldDefinition::create('text_long')
      ->setLabel($this->t('手动转帐方法说明'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

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
   * {@inheritdoc}
   */
  public function transfer(WithdrawInterface $withdraw): bool {
    return TRUE;
  }

}
