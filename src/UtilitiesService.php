<?php

namespace Drupal\commerce_canadapost;

use Drupal\commerce_canadapost\Api\Request;
use Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost;
use Drupal\commerce_store\Entity\StoreInterface;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class UtilitiesService.
 *
 * Contains helper functions for the Canada Post module.
 *
 * @package Drupal\commerce_canadapost
 */
class UtilitiesService extends Request {

  use StringTranslationTrait;

  /**
   * Build the form fields for the Canada Post API settings.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   * @param \Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost $shipping_method
   *   The Canada Post shipping method.
   *
   * @return array
   *   An array of form fields.
   *
   * @see \Drupal\commerce_canadapost\Form::buildForm()
   * @see \commerce_canadapost_form_alter()
   */
  public function buildApiForm(StoreInterface $store = NULL, CanadaPost $shipping_method = NULL) {
    $form = [];

    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('Canada Post API authentication'),
      '#open' => TRUE,
    ];

    // Display an option to use the shipping method settings or have specific
    // settings for this store.
    if (!$store) {
      $form['api']['use_store_settings'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Canada Post store API settings'),
        '#description' => $this->t('The Canada Post @url will be used when fetching rates and tracking details.
          <br \><strong>Uncheck</strong> this box if you\'d like to use a different account when fetching rates and tracking details for this shipping method.', [
            '@url' => Link::fromTextAndUrl(
              $this->t('API settings attached to the order store'),
              Url::fromRoute('entity.commerce_store.collection')
            )->toString(),
          ]
        ),
      ];
    }

    // Fetch the Canada Post API settings.
    $api_settings = $this->getApiSettings($store, $shipping_method);

    $form['api']['customer_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer number'),
      '#default_value' => $api_settings['customer_number'],
      '#required' => TRUE,
    ];
    $form['api']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $api_settings['username'],
      '#required' => TRUE,
    ];
    $form['api']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $api_settings['password'],
      '#required' => TRUE,
    ];
    $form['api']['contract_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contract ID'),
      '#default_value' => $api_settings['contract_id'],
    ];
    $form['api']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#default_value' => $api_settings['mode'],
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#required' => TRUE,
    ];
    $form['api']['log'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Log the following messages for debugging'),
      '#options' => [
        'request' => $this->t('API request messages'),
        'response' => $this->t('API response messages'),
      ],
      '#default_value' => $api_settings['log'],
    ];

    return $form;
  }

  /**
   * Encode the Canada Post API settings values in a json object.
   *
   * @param array $values
   *   The form_state values with the Canada Post API settings.
   *
   * @return object
   *   The encoded json object.
   */
  public function encodeSettings(array $values) {
    foreach ($this->getApiKeys() as $key) {
      $api_settings_values[$key] = $values[$key];
    }

    return json_encode($api_settings_values);
  }

}
