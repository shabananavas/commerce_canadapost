<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

use Drupal\commerce_canadapost\Api\TrackingService;
use Drupal\commerce_canadapost\UtilitiesService;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class CanadaPostApiSettingsTest.
 *
 * @coversDefaultClass \Drupal\commerce_canadapost\Api\Request
 * @group commerce_canadapost
 */
class CanadaPostApiSettingsTest extends CanadaPostUnitTestBase {

  /**
   * The Canada Post utilities service object.
   *
   * @var \Drupal\commerce_canadapost\UtilitiesService
   */
  protected $utilities;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $tracking_api = $this->prophesize(TrackingService::class);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->utilities = new UtilitiesService($tracking_api->reveal(), $entity_type_manager->reveal());
  }

  /**
   * ::covers getRequestConfig.
   */
  public function testShippingMethodApiSettingsReturned() {
    // Set the API settings w/o passing a store entity.
    $api_settings = $this->utilities->getApiSettings(NULL, $this->shippingMethod);

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
    // Set the API settings passing a store entity.
    $api_settings = $this->utilities->getApiSettings($this->shipment->getOrder()->getStore());

    // Now, test that we are returned back the store API settings.
    $this->assertEquals('store_mock_cn', $api_settings['customer_number']);
    $this->assertEquals('store_mock_name', $api_settings['username']);
    $this->assertEquals('store_mock_pwd', $api_settings['password']);
    $this->assertEquals('live', $api_settings['mode']);
  }

}
