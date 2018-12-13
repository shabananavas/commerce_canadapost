<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

use Drupal\commerce_canadapost\Api\Request;

/**
 * Class CanadaPostApiSettingsTest.
 *
 * @coversDefaultClass \Drupal\commerce_canadapost\Api\Request
 * @group commerce_canadapost
 */
class CanadaPostApiSettingsTest extends CanadaPostUnitTestBase {

  /**
   * ::covers getRequestConfig.
   */
  public function testSiteWideApiSettingsReturned() {
    // Set the API settings w/o passing a store entity.
    $request = new Request();
    $request->setApiSettings();

    // Now, test that we are returned back the sitewide API settings.
    $config = $request->getRequestConfig();

    $this->assertEquals('sitewide_mock_cn', $config['customer_number']);
    $this->assertEquals('sitewide_mock_name', $config['username']);
    $this->assertEquals('sitewide_mock_pwd', $config['password']);
    $this->assertEquals('dev', $config['env']);
  }

  /**
   * ::covers getRequestConfig.
   */
  public function testStoreApiSettingsReturned() {
    // Set the API settings passing a store entity.
    $request = new Request();
    $request->setApiSettings($this->shipment->getOrder()->getStore());

    // Now, test that we are returned back the store API settings.
    $config = $request->getRequestConfig();

    $this->assertEquals('store_mock_cn', $config['customer_number']);
    $this->assertEquals('store_mock_name', $config['username']);
    $this->assertEquals('store_mock_pwd', $config['password']);
    $this->assertEquals('prod', $config['env']);
  }

}
