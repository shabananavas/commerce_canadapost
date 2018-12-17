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
   * {@inheritdoc}
   */
  public function fetchTrackingSummary($tracking_pin, ShipmentInterface $shipment) {
    // Fetch the Canada Post API settings first.
    $store = $shipment->getOrder()->getStore();
    $shipping_method = $shipment->getShippingMethod();
    $api_settings = $this->getApiSettings($store, $shipping_method);

    try {
      $tracking = $this->getRequest($api_settings);
      $response = $tracking->getSummary($tracking_pin);
    }
    catch (ClientException $exception) {
      $message = sprintf(
        'An error has been returned by the Canada Post when fetching the tracking summary for the tracking PIN "%s". The error was: "%s"',
        $tracking_pin,
        json_encode($exception->getResponseBody())
      );
      $this->logger->error($message);

      return;
    }

    return $this->parseResponse($response);
  }

  /**
   * Returns a Canada Post request service api.
   *
   * @param array $api_settings
   *   The Canada Post API settings.
   *
   * @return \CanadaPost\Tracking
   *   The Canada Post tracking request service object.
   */
  protected function getRequest(array $api_settings) {
    $config = $this->getRequestConfig($api_settings);

    return $tracking = new Tracking($config);
  }

  /**
   * Parse results from Canada Post API into ShippingRates.
   *
   * @param array $response
   *   The response from the Canada Post API Rating service.
   *
   * @return \Drupal\commerce_shipping\ShippingRate[]
   *   The Canada Post shipping rates.
   */
  private function parseResponse(array $response) {
    if (!empty($response['tracking-summary']['pin-summary'])) {
      return $response['tracking-summary']['pin-summary'];
    }

    return [];
  }

}
