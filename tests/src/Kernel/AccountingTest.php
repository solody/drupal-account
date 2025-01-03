<?php

declare(strict_types=1);

namespace Drupal\Tests\account\Kernel;

use Drupal\account\Entity\Account;
use Drupal\account\Entity\AccountType;
use Drupal\account\Entity\LedgerInterface;
use Drupal\commerce_price\Price;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test description.
 */
#[Group('account')]
final class AccountingTest extends KernelTestBase {

  use UserCreationTrait {
    createRole as drupalCreateRole;
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['account', 'system', 'options', 'user', 'commerce', 'commerce_price', 'dynamic_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock necessary services here.
    $this->installEntitySchema('user');
    $this->installEntitySchema('account');
    $this->installEntitySchema('account_type');
    $this->installEntitySchema('ledger');
  }

  /**
   * Test callback.
   */
  public function testSomething(): void {
    $user = $this->createUser();
    $account_type = AccountType::create([
      'id' => 'something',
      'label' => 'something',
      'currency' => 'USD',
    ]);
    $account_type->save();
    self::assertEquals('USD', $account_type->getCurrency());
    $account = Account::create([
      'type' => $account_type->id(),
      'name' => 'something',
      'uid' => $user,
    ]);
    $account->save();

    /** @var \Drupal\account\FinanceManagerInterface $finance_manager */
    $finance_manager = $this->container->get('account.finance_manager');

    $account_auto_get = $finance_manager->getAccount($user, $account_type->id());
    self::assertEquals($account->id(), $account_auto_get->id());

    $account_auto_created = $finance_manager->createAccount($user, $account_type->id(), 'USD');
    self::assertEquals($account->id(), $account_auto_created->id());

    $user2 = $this->createUser();

    $account_auto_get = $finance_manager->getAccount($user2, $account_type->id());
    self::assertEmpty($account_auto_get);

    $account_auto_created = $finance_manager->createAccount($user2, $account_type->id(), 'USD');
    self::assertNotEquals($account->id(), $account_auto_created->id());

    $amount = new Price('100', 'USD');
    $finance_manager->createLedger(
      $account,
      LedgerInterface::AMOUNT_TYPE_DEBIT,
      $amount,
      'Something',
    );

    self::assertEquals(0, $amount->compareTo($finance_manager->computeAvailableBalance($account)));

    $amount2 = new Price('300', 'USD');
    $finance_manager->createLedger(
      $account,
      LedgerInterface::AMOUNT_TYPE_DEBIT,
      $amount2,
      'Something',
    );

    self::assertEquals(0, $amount->add($amount2)->compareTo($finance_manager->computeAvailableBalance($account)));
  }

}
