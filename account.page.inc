<?php

/**
 * @file
 * Contains account.page.inc.
 *
 * Page callback for Account entities.
 */

use Drupal\Core\Url;
use Drupal\Core\Render\Element;

/**
 * Prepares variables for Account templates.
 *
 * Default template: account.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_account(array &$variables) {
  // Fetch Account Entity Object.
  /** @var \Drupal\account\Entity\AccountInterface $account */
  $account = $variables['elements']['#account'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // Title.
  /** @var \Drupal\user\Entity\User $user */
  $user = $account->getOwner();
  /** @var \Drupal\account\Entity\AccountType $account_type */
  $account_type = $account->get('type')->entity;
  $variables['title'] = "{$account_type->label()}: {$user->getAccountName()}";

  $variables['links'][] = [
    '#title' => t('Show detail ledgers'),
    '#type' => 'link',
    '#url' => Url::fromRoute('entity.ledger.collection', ['account' => $account->id()]),
  ];
  // Button to adjust balance.
  $variables['links'][] = [
    '#title' => t('Adjust balance'),
    '#type' => 'link',
    '#url' => Url::fromRoute('account.manual_add_account_ledger_form', ['account_id' => $account->id()]),
  ];

  // Statistic table.
  $table = [
    '#type' => 'table',
    '#caption' => t('Statistic table'),
    '#header' => [
      t('Item'),
      t('Amount'),
    ],
  ];

  $profit_data = [
    [
      'name' => t('Total debit'),
      'amount' => $variables['elements']['total_debit'][0]['#markup'],
    ],
    [
      'name' => t('Total credit'),
      'amount' => $variables['elements']['total_credit'][0]['#markup'],
    ],
    [
      'name' => t('Balance'),
      'amount' => $variables['elements']['balance'][0]['#markup'],
    ],
  ];

  foreach ($profit_data as $i => $data) {
    $table[$i]['#attributes'] = [
      'class' => [
        'foo',
        'baz',
      ],
    ];

    $table[$i]['name'] = [
      '#markup' => $data['name'],
    ];
    $table[$i]['amount'] = [
      '#markup' => $data['amount'],
    ];
  }

  $variables['table'] = $table;
}
