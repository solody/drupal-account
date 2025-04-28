<?php

namespace Drupal\account;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Ledger entities.
 *
 * @ingroup account
 */
class LedgerListBuilder extends EntityListBuilder {

  /**
   * Constructs a new PaymentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    protected RouteMatchInterface $routeMatch,
    protected CurrencyFormatterInterface $currencyFormatter,
  ) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('commerce_price.currency_formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ledger ID');
    $header['name'] = $this->t('Name');
    $header['amount'] = $this->t('Amount');
    $header['amount_type'] = $this->t('Amount type');
    $header['balance'] = $this->t('Balance');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\account\Entity\Ledger $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ledger.edit_form',
      [
        'ledger' => $entity->id(),
        'account' => $entity->getAccountId(),
      ]
    );
    $amount = $entity->getAmount();
    $formatted_amount = $this->currencyFormatter->format($amount->getNumber(), $amount->getCurrencyCode());
    $row['amount'] = $formatted_amount;
    $row['amount_type'] = $entity->get('amount_type')->value == 'debit' ? $this->t('Debit') : $this->t('Credit');

    $balance = $entity->getBalance();
    $formatted_balance = $this->currencyFormatter->format($balance->getNumber(), $balance->getCurrencyCode());
    $row['balance'] = $formatted_balance;
    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityListQuery(): QueryInterface {
    $query = parent::getEntityListQuery();
    $account = $this->routeMatch->getParameter('account');
    $query->condition('account_id', $account);
    $query->sort('ledger_id', 'DESC');
    return $query;
  }

}
