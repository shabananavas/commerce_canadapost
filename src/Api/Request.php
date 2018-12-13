<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_store\Entity\StoreInterface;

use Exception;

/**
 * CanadaPost API Service.
 *
 * @package Drupal\commerce_canadapost
 */
class Request implements RequestInterface {

  /**
   * The Canada Post API settings.
   *
   * @var array
   */
  protected $apiSettings;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Request class constructor.
   */
  public function __construct() {
    $this->configFactory = \Drupal::service('config.factory');
    $this->logger = \Drupal::service('logger.factory')->get(COMMERCE_CANADAPOST_LOGGER_CHANNEL);
  }

  /**
   * {@inheritdoc}
   */
  public function setApiSettings(StoreInterface $store = NULL) {
    $api_settings = [];

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

    $this->apiSettings = $api_settings;

    return $this->apiSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestConfig() {
    // Verify necessary configuration is available.
    if (empty($this->apiSettings['username'])
      || empty($this->apiSettings['password'])
      || empty($this->apiSettings['customer_number'])) {
      throw new Exception('Configuration is required.');
    }

    $config = [
      'username' => $this->apiSettings['username'],
      'password' => $this->apiSettings['password'],
      'customer_number' => $this->apiSettings['customer_number'],
      'contract_id' => $this->apiSettings['contract_id'],
      'env' => $this->getEnvironmentMode(),
    ];

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiKeys() {
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
   * Convert the environment mode to the correct format for the SDK.
   *
   * @return string
   *   The environment mode (prod/dev).
   */
  protected function getEnvironmentMode() {
    return $this->apiSettings['mode'] === 'live' ? 'prod' : 'dev';
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
