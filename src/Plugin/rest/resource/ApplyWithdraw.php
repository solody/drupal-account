<?php

namespace Drupal\account\Plugin\rest\resource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Routing\Route;
use Drupal\commerce_price\Price;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\account\Entity\Account;
use Drupal\account\FinanceManagerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "account_apply_withdraw",
 *   label = @Translation("Apply withdraw"),
 *   uri_paths = {
 *     "create" = "/api/rest/account/apply-withdraw/{account}"
 *   }
 * )
 */
class ApplyWithdraw extends ResourceBase {

  use StringTranslationTrait;

  /**
   * Constructs a new ApplyWithdraw object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user instance.
   * @param \Drupal\account\FinanceManagerInterface $financeManager
   *   The finance manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    private readonly AccountProxyInterface $currentUser,
    private readonly FinanceManagerInterface $financeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('account'),
      $container->get('current_user'),
      $container->get('account.finance_manager')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\account\Entity\Account $account
   *   The account entity.
   * @param array $data
   *   The posted data.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function post(Account $account, array $data): ModifiedResourceResponse {

    if (!$this->currentUser->hasPermission('apply withdraw for own finance account') ||
         $account->getOwnerId() !== $this->currentUser->id()) {
      throw new AccessDeniedHttpException('You do not have permission to apply withdraw for this account.');
    }

    $transfer_method = NULL;
    /** @var \Drupal\account\TransferMethodStorage $methodStorage */
    $methodStorage = \Drupal::entityTypeManager()->getStorage('account_transfer_method');
    if (isset($data['transfer_method'])) {
      $transfer_method = $methodStorage->load($data['transfer_method']);
    }
    else {
      // Try to load the default transfer method.
      $transfer_method = $methodStorage->loadDefault($account->getOwner()->id());
    }
    if (!$transfer_method) {
      throw new BadRequestHttpException("'Transfer method {$data['transfer_method']} can not be found.'");
    }

    try {
      $withdraw = $this->financeManager->applyWithdraw(
        $account,
        new Price($data['amount'], $account->getCurrency()),
        $transfer_method,
        $data['remarks'],
      );
    }
    catch (\Exception $e) {
      throw new BadRequestHttpException($e->getMessage(), $e);
    }

    return new ModifiedResourceResponse($withdraw, 200);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method): Route {
    $route = parent::getBaseRoute($canonical_path, $method);
    $parameters = $route->getOption('parameters') ?: [];
    $parameters['account']['type'] = 'entity:account';
    $route->setOption('parameters', $parameters);

    return $route;
  }

}
