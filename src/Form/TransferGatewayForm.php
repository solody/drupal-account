<?php

namespace Drupal\account\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\account\Plugin\TransferGatewayManager;
use Drupal\Component\Utility\Html;

/**
 * Class TransferGatewayForm.
 */
class TransferGatewayForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\account\Entity\TransferGatewayInterface $gateway */
    $gateway = $this->entity;
    /** @var TransferGatewayManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.account_transfer_gateway');
    $plugins = array_column($plugin_manager->getDefinitions(), 'label', 'id');
    asort($plugins);

    // Use the first available plugin as the default value.
    if (!$gateway->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin = reset($plugin_ids);
      $gateway->setPluginId($plugin);
    }
    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('plugin', $gateway->getPluginId());
    // Pass the plugin configuration only if the plugin hasn't been changed via #ajax.
    $plugin_configuration = $gateway->getPluginId() == $plugin ? $gateway->getPluginConfiguration() : [];

    $wrapper_id = Html::getUniqueId('shipping-method-form');
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $gateway->label(),
      '#description' => $this->t("Label for the Transfer gateway."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $gateway->id(),
      '#machine_name' => [
        'exists' => '\Drupal\account\Entity\TransferGateway::load',
      ],
      '#disabled' => !$gateway->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#disabled' => !$gateway->isNew(),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => 'account_transfer_gateway',
      '#plugin_id' => $plugin,
      '#default_value' => $plugin_configuration,
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
    $gateway = $this->entity;
    $gateway->setPluginConfiguration($form_state->getValue(['configuration']));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $transfer_gateway = $this->entity;
    $status = $transfer_gateway->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Transfer gateway.', [
          '%label' => $transfer_gateway->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Transfer gateway.', [
          '%label' => $transfer_gateway->label(),
        ]));
    }
    $form_state->setRedirectUrl($transfer_gateway->toUrl('collection'));
  }

}
