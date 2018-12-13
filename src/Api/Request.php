<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CanadaPost API Service.
 *
 * @package Drupal\commerce_canadapost
 */
class Request implements RequestInterface {

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
  public function getApiSettings(StoreInterface $store = NULL) {
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

    return $api_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestConfig(array $api_settings) {
    // Verify necessary configuration is available.
    if (empty($api_settings['username'])
      || empty($api_settings['password'])
      || empty($api_settings['customer_number'])) {
      throw new Exception('Configuration is required.');
    }

    $config = [
      'username' => $api_settings['username'],
      'password' => $api_settings['password'],
      'customer_number' => $api_settings['customer_number'],
      'contract_id' => $api_settings['contract_id'],
      'env' => $this->getEnvironmentMode($api_settings),
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
   * @param array $api_settings
   *   The Canada Post API settings.
   *
   * @return string
   *   The environment mode (prod/dev).
   */
  protected function getEnvironmentMode(array $api_settings) {
    return $api_settings['mode'] === 'live' ? 'prod' : 'dev';
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
