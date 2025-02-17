<?php

namespace Drupal\account\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Transfer gateway entity.
 *
 * @ConfigEntityType(
 *   id = "account_transfer_gateway",
 *   label = @Translation("Transfer gateway"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\account\TransferGatewayListBuilder",
 *     "form" = {
 *       "add" = "Drupal\account\Form\TransferGatewayForm",
 *       "edit" = "Drupal\account\Form\TransferGatewayForm",
 *       "delete" = "Drupal\account\Form\TransferGatewayDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\account\TransferGatewayHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "account_transfer_gateway",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "transfer_gateway_id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/finance/account_transfer_gateway/{account_transfer_gateway}",
 *     "add-form" = "/admin/finance/account_transfer_gateway/add",
 *     "edit-form" = "/admin/finance/account_transfer_gateway/{account_transfer_gateway}/edit",
 *     "delete-form" = "/admin/finance/account_transfer_gateway/{account_transfer_gateway}/delete",
 *     "collection" = "/admin/finance/account_transfer_gateway"
 *   }
 * )
 */
class TransferGateway extends ConfigEntityBase implements TransferGatewayInterface {

  /**
   * The Transfer gateway ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Transfer gateway label.
   *
   * @var string
   */
  protected $label;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    /** @var \Drupal\account\Plugin\TransferGatewayManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.account_transfer_gateway');
    return $plugin_manager->createInstance($this->plugin, $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    $this->configuration = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginConfiguration(array $configuration) {
    $this->configuration = $configuration;
    return $this;
  }

}
