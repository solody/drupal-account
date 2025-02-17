<?php

namespace Drupal\account\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Ledger entity.
 *
 * @ingroup account
 *
 * @ContentEntityType(
 *   id = "ledger",
 *   label = @Translation("Ledger"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\account\LedgerListBuilder",
 *     "views_data" = "Drupal\account\Entity\LedgerViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\account\Form\LedgerForm",
 *       "add" = "Drupal\account\Form\LedgerForm",
 *       "edit" = "Drupal\account\Form\LedgerForm",
 *       "delete" = "Drupal\account\Form\LedgerDeleteForm",
 *     },
 *     "access" = "Drupal\account\LedgerAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\account\LedgerHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ledger",
 *   admin_permission = "administer ledger entities",
 *   entity_keys = {
 *     "id" = "ledger_id",
 *     "label" = "remarks",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/finance/account/{account}/ledgers/{ledger}",
 *     "add-form" = "/admin/finance/account/{account}/ledgers/add",
 *     "edit-form" = "/admin/finance/account/{account}/ledgers/{ledger}/edit",
 *     "delete-form" = "/admin/finance/account/{account}/ledgers/{ledger}/delete",
 *     "collection" = "/admin/finance/account/{account}/ledgers",
 *   },
 *   field_ui_base_route = "ledger.settings"
 * )
 */
class Ledger extends ContentEntityBase implements LedgerInterface {


  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['account'] = $this->getAccountId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount(): Account {
    return $this->get('account_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId(): int {
    return $this->get('account_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountType(): string {
    return $this->getAccount()->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getAmountType(): string {
    return $this->get('amount_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount(): Price {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
    return new Price(0, $this->getAccount()->getCurrencyCode());
  }

  /**
   * {@inheritdoc}
   */
  public function getBalance(): Price {
    if (!$this->get('balance')->isEmpty()) {
      return $this->get('balance')->first()->toPrice();
    }
    return new Price(0, $this->getAccount()->getCurrencyCode());
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
  public function setCreatedTime(int $timestamp): LedgerInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // 所属账户.
    $fields['account_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Account'))
      ->setSetting('target_type', 'account')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ]);

    // 记账类型（进/出）.
    $fields['amount_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Amount type'))
      ->setSettings([
        'allowed_values' => [
          LedgerInterface::AMOUNT_TYPE_CREDIT => t('Credit'),
          LedgerInterface::AMOUNT_TYPE_DEBIT => t('Debit'),
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ]);

    // 记账金额.
    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_list_price',
        'weight' => 0,
      ]);

    // 记账余额.
    $fields['balance'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Balance'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_list_price',
        'weight' => 0,
      ]);

    // 备注.
    $fields['remarks'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Remarks'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'label' => 'above',
        'type' => 'string_textarea',
        'weight' => 0,
      ]);

    $fields['source'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Accounting source'))
      ->setDisplayOptions('view', [
        'type' => 'dynamic_entity_reference_label',
      ]);

    // Whether to send message to the owner about this record or not.
    $fields['notice'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Need notice the owner.'))
      ->setDefaultValue(TRUE);

    // 发生时间.
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
