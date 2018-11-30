<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

/**
 * Class CanadaPostRateRequestTest.
 *
 * @coversDefaultClass \Drupal\commerce_canadapost\Api\RatingService
 * @group commerce_canadapost
 */
class CanadaPostRateRequestTest extends CanadaPostUnitTestBase {

  /**
   * ::covers getRates.
   */
  public function testGetRates() {
    $shipping_method = $this->mockShippingMethod();
    $shipment = $this->mockShipment();

    $rates = $this->ratingService->getRates($shipping_method, $shipment, []);

    // Test the parsed response.
    foreach ($rates as $rate) {
      /* @var \Drupal\commerce_shipping\ShippingRate $rate */
      $this->assertInstanceOf('Drupal\commerce_shipping\ShippingRate', $rate);
      $this->assertInstanceOf('Drupal\commerce_price\Price', $rate->getAmount());
      $this->assertGreaterThan(0, $rate->getAmount()->getNumber());
      $this->assertEquals($rate->getAmount()->getCurrencyCode(), 'CAD');
      $this->assertNotEmpty($rate->getService()->getLabel());
    }
    $this->assertTrue(is_array($rates));
  }

}
