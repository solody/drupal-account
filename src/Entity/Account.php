<?php

namespace Drupal\account\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Account entity.
 *
 * @ingroup account
 *
 * @ContentEntityType(
 *   id = "account",
 *   label = @Translation("Account"),
 *   label_collection = @Translation("Accounts"),
 *   label_singular = @Translation("Account"),
 *   label_plural = @Translation("Accounts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Accounts",
 *     plural = "@count Accounts",
 *   ),
 *   bundle_label = @Translation("Account type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\account\AccountListBuilder",
 *     "views_data" = "Drupal\account\Entity\AccountViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\account\Form\AccountForm",
 *       "add" = "Drupal\account\Form\AccountForm",
 *       "edit" = "Drupal\account\Form\AccountForm",
 *       "delete" = "Drupal\account\Form\AccountDeleteForm",
 *     },
 *     "access" = "Drupal\account\AccountAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\account\AccountHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "account",
 *   admin_permission = "administer account entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/finance/account/{account}",
 *     "add-page" = "/admin/finance/account/add",
 *     "add-form" = "/admin/finance/account/add/{account_type}",
 *     "edit-form" = "/admin/finance/account/{account}/edit",
 *     "delete-form" = "/admin/finance/account/{account}/delete",
 *     "collection" = "/admin/finance/account",
 *   },
 *   bundle_entity_type = "account_type",
 *   field_ui_base_route = "entity.account_type.edit_form"
 * )
 */
class Account extends ContentEntityBase implements AccountInterface {

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
  public function setName(string $name): AccountInterface {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode(): string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode(string $currency_code): AccountInterface {
    $this->set('currency_code', $currency_code);
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
  public function setCreatedTime(int $timestamp): AccountInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalance(): Price {
    if (!$this->get('balance')->isEmpty()) {
      return $this->get('balance')->first()->toPrice();
    }
    return new Price(0, $this->getCurrencyCode());
  }

  /**
   * {@inheritdoc}
   */
  public function setBalance(Price $amount): AccountInterface {
    $this->set('balance', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCredit(): Price {
    if (!$this->get('total_credit')->isEmpty()) {
      return $this->get('total_credit')->first()->toPrice();
    }
    return new Price(0, $this->getCurrencyCode());
  }

  /**
   * {@inheritdoc}
   */
  public function setTotalCredit(Price $amount): AccountInterface {
    $this->set('total_credit', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalDebit(): Price {
    if (!$this->get('total_debit')->isEmpty()) {
      return $this->get('total_debit')->first()->toPrice();
    }
    return new Price(0, $this->getCurrencyCode());
  }

  /**
   * {@inheritdoc}
   */
  public function setTotalDebit(Price $amount): AccountInterface {
    $this->set('total_debit', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // 账户所属用户.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
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

    // 账户名称.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Account name'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    // 账户货币类型.
    $fields['currency_code'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Account currency code'))
      ->setDefaultValue('USD')
      ->setSetting('allowed_values_function', 'Drupal\account\Entity\Account::getAllowedCurrencyCodes')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ]);

    // 账户进项累计（借记）.
    $fields['total_debit'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total debit'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_list_price',
        'weight' => 0,
      ]);

    // 账户出项累计（贷记）.
    $fields['total_credit'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total credit'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_list_price',
        'weight' => 0,
      ]);

    // 账户余额.
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

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 0,
      ]);

    return $fields;
  }

  /**
   * Get allowed currency codes.
   *
   * @todo Read from commerce.
   */
  public static function getAllowedCurrencyCodes(): array {
    return [
      'USD' => 'USD',
      'CNY' => 'CNY',
    ];
  }

}
