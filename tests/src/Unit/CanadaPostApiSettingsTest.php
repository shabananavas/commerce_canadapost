<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

use Drupal\commerce_canadapost\UtilitiesService;

/**
 * Class CanadaPostApiSettingsTest.
 *
 * @coversDefaultClass \Drupal\commerce_canadapost\Api\RequestServiceBase
 * @group commerce_canadapost
 */
class CanadaPostApiSettingsTest extends CanadaPostUnitTestBase {

  /**
   * ::covers getRequestConfig.
   */
  public function testShippingMethodApiSettingsReturned() {
    // Get the API settings w/o passing a store entity.
    $utilities = new UtilitiesService();
    $api_settings = $utilities->getApiSettings(NULL, $this->shippingMethod);

    // Now, test that we are returned back the sitewide API settings.
    $this->assertEquals('shipment_method_mock_cn', $api_settings['customer_number']);
    $this->assertEquals('shipment_method_mock_name', $api_settings['username']);
    $this->assertEquals('shipment_method_mock_pwd', $api_settings['password']);
    $this->assertEquals('test', $api_settings['mode']);
  }

  /**
   * ::covers getRequestConfig.
   */
  public function testStoreApiSettingsReturned() {
    // Get the API settings passing a store entity.
    $utilities = new UtilitiesService();
    $api_settings = $utilities->getApiSettings($this->shipment->getOrder()->getStore());

    // Now, test that we are returned back the store API settings.
    $this->assertEquals('store_mock_cn', $api_settings['customer_number']);
    $this->assertEquals('store_mock_name', $api_settings['username']);
    $this->assertEquals('store_mock_pwd', $api_settings['password']);
    $this->assertEquals('live', $api_settings['mode']);
  }

  /**
   * ::covers getRequestConfig.
   */
  public function testShippingMethodHasPreference() {
    // Get the API settings passing a store entity and shipping method entity
    // and test that we get back the shipping method settings as that has
    // preference.
    $utilities = new UtilitiesService();
    $api_settings = $utilities->getApiSettings($this->shipment->getOrder()->getStore(), $this->shippingMethod);

    // Now, test that we are returned back the store API settings.
    $this->assertEquals('shipment_method_mock_cn', $api_settings['customer_number']);
    $this->assertEquals('shipment_method_mock_name', $api_settings['username']);
    $this->assertEquals('shipment_method_mock_pwd', $api_settings['password']);
    $this->assertEquals('test', $api_settings['mode']);
  }

}
