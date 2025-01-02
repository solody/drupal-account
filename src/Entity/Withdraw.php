<?php

namespace Drupal\account\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Withdraw entity.
 *
 * @ingroup account
 *
 * @ContentEntityType(
 *   id = "withdraw",
 *   label = @Translation("Withdraw"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\account\WithdrawListBuilder",
 *     "views_data" = "Drupal\account\Entity\WithdrawViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\account\Form\WithdrawForm",
 *       "add" = "Drupal\account\Form\WithdrawForm",
 *       "edit" = "Drupal\account\Form\WithdrawForm",
 *       "delete" = "Drupal\account\Form\WithdrawDeleteForm",
 *     },
 *     "access" = "Drupal\account\WithdrawAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\account\WithdrawHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "withdraw",
 *   admin_permission = "administer withdraw entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/finance/withdraw/{withdraw}",
 *     "add-form" = "/admin/finance/withdraw/add",
 *     "edit-form" = "/admin/finance/withdraw/{withdraw}/edit",
 *     "delete-form" = "/admin/finance/withdraw/{withdraw}/delete",
 *     "collection" = "/admin/finance/withdraw",
 *   },
 *   field_ui_base_route = "withdraw.settings"
 * )
 */
class Withdraw extends ContentEntityBase implements WithdrawInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName(string $name): WithdrawInterface {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionNumber(): string {
    return $this->get('transaction_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransactionNumber(string $transaction_number): WithdrawInterface {
    $this->set('transaction_number', $transaction_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): WithdrawInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount(): \Drupal\commerce_price\Price {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
    return new Price(0, $this->getAccount()->getCurrencyCode());
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount(): AccountInterface {
    return $this->get('account_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransferMethod(TransferMethodInterface $transfer_method): WithdrawInterface {
    $this->set('transfer_method', $transfer_method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransferMethod(): TransferMethodInterface {
    return $this->get('transfer_method')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Withdraw entity.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ]);

    // 提现账户.
    $fields['account_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Account'))
      ->setSetting('target_type', 'account')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ]);

    // 提现金额.
    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ]);

    // 转账方式.
    $fields['transfer_method'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Transfer method'))
      ->setSetting('target_type', 'account_transfer_method')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ]);

    // 处理状态, See account.workflows.yml.
    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('Process status'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 0,
      ])
      ->setSetting('workflow', 'withdraw_default');

    // 处理人（审核人）.
    $fields['auditor_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Auditor'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ]);

    // 审核时间.
    $fields['audit_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Audit time'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 0,
      ]);

    // 转账交易号.
    $fields['transaction_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Transaction number'))
      ->setDescription(t('The three-part transfer service system transaction number.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    // 备注.
    $fields['remarks'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Remarks'))
      ->setSettings([
        'max_length' => 250,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
      ]);

    $fields['notice'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Need notice the owner.'))
      ->setDefaultValue(TRUE);

    // 申请时间.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 0,
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
