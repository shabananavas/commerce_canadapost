<?php

namespace Drupal\commerce_canadapost;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use function json_encode;
use function json_decode;

/**
 * Class UtilitiesService.
 *
 * Contains helper functions for the Canada Post module.
 *
 * @package Drupal\commerce_canadapost
 */
class UtilitiesService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a UtilitiesService class.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Fetch the Canada Post API settings, first from the store then the site.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   *
   * @return array
   *   An array of api settings.
   */
  public function getApiSettings(StoreInterface $store = NULL) {
    // If we have store specific settings, return that.
    if ($store && !empty($store->get('canadapost_api_settings')->getValue()[0]['value'])) {
      $api_settings = $this->parseSettings(
        $store->get('canadapost_api_settings')->getValue()[0]['value']
      );
    }
    // Else, we fetch it from the sitewide settings.
    else {
      $config = $this->configFactory->get('commerce_canadapost.settings');

      foreach ($this->getApiKeys() as $key) {
        $api_settings[$key] = $config->get("api.$key");
      }
    }

    return $api_settings;
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

  /**
   * Build the form fields for the Canada Post API settings.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   *
   * @return array
   *   An array of form fields.
   *
   * @see \Drupal\commerce_canadapost\Form::buildForm()
   * @see \commerce_canadapost_form_alter()
   */
  public function buildApiForm(StoreInterface $store = NULL) {
    $form = [];

    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('Canada Post API authentication'),
      '#open' => TRUE,
    ];

    // Display an option to use the sitewide settings or have specific
    // settings for this store.
    if ($store) {
      $store_settings_set = empty($store->get('canadapost_api_settings')
        ->getValue()[0]['value']);

      $form['api']['use_sitewide_settings'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Canada Post sitewide API settings'),
        '#description' => $this->t('The Canada Post @url will be used when fetching rates and tracking details.
          <br \><strong>Uncheck</strong> this box if you\'d like to use a different account when fetching rates and tracking details for orders from this store.', [
            '@url' => Link::fromTextAndUrl(
              $this->t('sitewide API settings'),
              Url::fromRoute('commerce_canadapost.settings_form')
            )->toString(),
          ]
        ),
        '#default_value' => $store_settings_set,
      ];
    }

    // Fetch the Canada Post API settings.
    $api_settings = $this->getApiSettings($store);

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

    // Alter the fields if we're in the store form.
    if ($store) {
      $this->alterApiFormFields($form);
    }

    return $form;
  }

  /**
   * Alter the Canada Post API settings form fields if we're in the store form.
   *
   * @param array $form
   *   The form array.
   */
  protected function alterApiFormFields(array &$form) {
    // Fields should be visible only if the use_site_settings checkbox is
    // unchecked.
    $states = [
      'visible' => [
        ':input[name="use_sitewide_settings"]' => [
          'checked' => FALSE,
        ],
      ],
      'required' => [
        ':input[name="use_sitewide_settings"]' => [
          'checked' => FALSE,
        ],
      ],
    ];
    foreach ($this->getApiKeys() as $key) {
      $form['api'][$key]['#states'] = $states;
      $form['api'][$key]['#required'] = FALSE;
    }

    // Contract ID and Log are not required so remove it from the states as
    // well.
    unset($form['api']['contract_id']['#states']['required']);
    unset($form['api']['log']['#states']['required']);
  }

  /**
   * Return the Canada Post API keys.
   *
   * @return array
   *   An array of API setting keys.
   */
  protected function getApiKeys() {
    return [
      'customer_number',
      'username',
      'password',
      'contract_id',
      'mode',
      'log',
    ];
  }

  /**
   * Parse the Canada Post API settings stored as json in the store entity.
   *
   * @param object $api_settings
   *   The json encoded Canada Post api settings.
   *
   * @return array
   *   An array of values extracted from the json object.
   */
  protected function parseSettings($api_settings) {
    return json_decode($api_settings, TRUE);
  }

}
