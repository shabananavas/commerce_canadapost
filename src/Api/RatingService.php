<?php

namespace Drupal\commerce_canadapost\Api;

use CanadaPost\Exception\ClientException;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use CanadaPost\Rating;

/**
 * Provides the default Rating API integration services.
 */
class RatingService implements RatingServiceInterface {

  /**
   * The Canada Post configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new TrackingService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->config = $config_factory->get('commerce_canadapost.settings');
    $this->logger = $logger_factory->get(COMMERCE_CANADAPOST_LOGGER_CHANNEL);
  }

  /**
   * {@inheritdoc}
   */
  public function getRates(ShippingMethodInterface $shipping_method, ShipmentInterface $shipment, array $options) {
    $order = $shipment->getOrder();
    $origin_postal_code = !empty($shipping_method->getConfiguration()['shipping_information']['origin_postal_code'])
      ? $shipping_method->getConfiguration()['shipping_information']['origin_postal_code']
      : $order->getStore()
        ->getAddress()
        ->getPostalCode();
    $postal_code = $shipment->getShippingProfile()
      ->get('address')
      ->first()
      ->getPostalCode();
    $weight = $shipment->getWeight()->convert('kg')->getNumber();

    try {
      $request = $this->getRequest();
      $response = $request->getRates($origin_postal_code, $postal_code, $weight, $options);
    }
    catch (ClientException $exception) {
      $message = sprintf(
        'An error has been returned by the Canada Post when fetching the shipping rates. The error was: "%s"',
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
   * @return \CanadaPost\Rating
   *   The Canada Post request service object.
   */
  public function getRequest() {
    $config = [
      'username' => $this->config->get('api.username'),
      'password' => $this->config->get('api.password'),
      'customer_number' => $this->config->get('api.customer_number'),
      'contract_id' => $this->config->get('api.contract_id'),
      'env' => $this->getEnvironmentMode(),
    ];

    return new Rating($config);
  }

  /**
   * Convert the environment mode to the correct format for the SDK.
   */
  private function getEnvironmentMode() {
    return $this->config->get('api.mode') === 'live' ? 'prod' : 'dev';
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
    if (empty($response['price-quotes'])) {
      return [];
    }

    $rates = [];
    foreach ($response['price-quotes']['price-quote'] as $rate) {
      $service_code = $rate['service-code'];
      $service_name = $rate['service-name'];
      $price = new Price((string) $rate['price-details']['due'], 'CAD');

      $shipping_service = new ShippingService(
        $service_code,
        $service_name
      );
      $rates[] = new ShippingRate(
        $service_code,
        $shipping_service,
        $price
      );
    }

    return $rates;
  }

}
