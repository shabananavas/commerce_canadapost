<?php

namespace Drupal\commerce_canadapost\Api;

use CanadaPost\Exception\ClientException;
use Drupal\commerce_canadapost\UtilitiesService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use CanadaPost\Tracking;

/**
 * Provides the default Tracking API integration services.
 */
class TrackingService implements TrackingServiceInterface {

  /**
   * The Canada Post utilities service object.
   *
   * @var \Drupal\commerce_canadapost\UtilitiesService
   */
  protected $service;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Canada Post API settings.
   *
   * @var array
   */
  protected $apiSettings;

  /**
   * Constructs a new TrackingService object.
   *
   * @param \Drupal\commerce_canadapost\UtilitiesService $service
   *   The Canada Post utilities service object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    UtilitiesService $service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->service = $service;
    $this->logger = $logger_factory->get(COMMERCE_CANADAPOST_LOGGER_CHANNEL);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchTrackingSummary($tracking_pin) {
    // Fetch the Canada Post API settings first.
    $this->apiSettings = $this->service->getApiSettings();

    try {
      // Turn on output buffering if we are in test mode.
      $test_mode = $this->apiSettings['mode'] === 'test';
      if ($test_mode) {
        ob_start();
      }

      $tracking = $this->getRequest();
      $response = $tracking->getSummary($tracking_pin);

      // Log the output buffer if we are in test mode.
      if ($test_mode) {
        $output = ob_get_contents();
        ob_end_clean();

        if (!empty($output)) {
          $this->logger->info($output);
        }
      }

      if ($this->apiSettings['log']['request']) {
        $response_output = print_r($response, TRUE);
        $message = sprintf(
          'Tracking request made for tracking pin: "%s". Response received: "%s".',
          $tracking_pin,
          $response_output
        );
        $this->logger->info($message);
      }
    }
    catch (ClientException $exception) {
      if ($this->apiSettings['log']['response']) {
        $message = sprintf(
          'An error has been returned by the Canada Post shipment method when fetching the tracking summary for the tracking PIN "%s". The error was: "%s"',
          $tracking_pin,
          json_encode($exception->getResponseBody())
        );
        $this->logger->error($message);
      }

      return [];
    }

    return $this->parseResponse($response);
  }

  /**
   * Returns a Canada Post request service api.
   *
   * @return \CanadaPost\Tracking
   *   The Canada Post tracking request service object.
   */
  protected function getRequest() {
    $config = [
      'username' => $this->apiSettings['username'],
      'password' => $this->apiSettings['password'],
      'customer_number' => $this->apiSettings['customer_number'],
      'contract_id' => $this->apiSettings['contract_id'],
      'env' => $this->getEnvironmentMode(),
    ];

    return $tracking = new Tracking($config);
  }

  /**
   * Convert the environment mode to the correct format for the SDK.
   */
  private function getEnvironmentMode() {
    return $this->apiSettings['mode'] === 'live' ? 'prod' : 'dev';
  }

  /**
   * Parse results from Canada Post API into ShippingRates.
   *
   * @param array $response
   *   The response from the Canada Post API Rating service.
   *
   * @return array
   *   The tracking summary from Canada Post.
   */
  private function parseResponse(array $response) {
    if (!empty($response['tracking-summary']['pin-summary'])) {
      return $response['tracking-summary']['pin-summary'];
    }

    return [];
  }

}
