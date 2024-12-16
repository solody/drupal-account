<?php

namespace Drupal\account\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Transfer method entity.
 *
 * @ingroup account
 *
 * @ContentEntityType(
 *   id = "account_transfer_method",
 *   label = @Translation("Transfer method"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\account\TransferMethodListBuilder",
 *     "views_data" = "Drupal\account\Entity\TransferMethodViewsData",
 *     "storage" = "Drupal\account\TransferMethodStorage",
 *     "form" = {
 *       "default" = "Drupal\account\Form\TransferMethodForm",
 *       "add" = "Drupal\account\Form\TransferMethodForm",
 *       "edit" = "Drupal\account\Form\TransferMethodForm",
 *       "delete" = "Drupal\account\Form\TransferMethodDeleteForm",
 *     },
 *     "access" = "Drupal\account\TransferMethodAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\account\TransferMethodHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "account_transfer_method",
 *   admin_permission = "administer transfer method entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "bundle" = "type",
 *   },
 *   links = {
 *     "canonical" = "/admin/account/account_transfer_method/{account_transfer_method}",
 *     "add-form" = "/admin/account/account_transfer_method/add",
 *     "edit-form" = "/admin/account/account_transfer_method/{account_transfer_method}/edit",
 *     "delete-form" = "/admin/account/account_transfer_method/{account_transfer_method}/delete",
 *     "collection" = "/admin/account/account_transfer_method",
 *   },
 *   field_ui_base_route = "account_transfer_method.settings",
 *   bundle_label = @Translation("Transfer method type"),
 *   bundle_plugin_type = "account_transfer_gateway"
 * )
 */
class TransferMethod extends ContentEntityBase implements TransferMethodInterface {

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
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransferGateway(TransferGatewayInterface $transfer_gateway) {
    $this->set('transfer_gateway', $transfer_gateway);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransferGateway() {
    return $this->get('transfer_gateway')->entity;
  }

  /**
   *
   */
  public function isDefault() {
    return (boolean) $this->is_default->value;
  }

  /**
   *
   */
  public function setDefault($value) {
    $this->set('is_default', (boolean) $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['transfer_gateway'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Transfer gateway'))
      ->setDescription(t('The transfer gateway.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'account_transfer_gateway')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
      ]);

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default transfer method.'))
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
