<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

use CanadaPost\Exception\ClientException;
use CanadaPost\Tracking;

/**
 * Provides the default Tracking API integration services.
 */
class TrackingService extends Request implements TrackingServiceInterface {

  /**
   * The Canada Post API settings.
   *
   * @var array
   */
  protected $apiSettings;

  /**
   * {@inheritdoc}
   */
  public function fetchTrackingSummary($tracking_pin, ShipmentInterface $shipment) {
    // Fetch the Canada Post API settings first.
    $store = $shipment->getOrder()->getStore();
    $this->apiSettings = $this->getApiSettings($store);

    try {
      // Turn on output buffering if we are in test mode.
      $test_mode = $this->apiSettings['mode'] === 'test';
      if ($test_mode) {
        ob_start();
      }

      $config = $this->getRequestConfig($this->apiSettings);
      $tracking = new Tracking($config);
      $response = $tracking->getSummary($tracking_pin);

      if ($this->apiSettings['log']['request']) {
        $response_output = var_export($response, TRUE);
        $message = sprintf(
          'Tracking request made for tracking pin: "%s". Response received: "%s".',
          $tracking_pin,
          $response_output
        );
        $this->logger->info($message);
      }

      $response = $this->parseResponse($response);
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

      $response = [];
    }

    // Log the output buffer if we are in test mode.
    if ($test_mode) {
      $output = ob_get_contents();
      ob_end_clean();

      if (!empty($output)) {
        $this->logger->info($output);
      }
    }

    return $response;
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
