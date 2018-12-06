<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

use CommerceGuys\Addressing\AddressInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\physical\Length;
use Drupal\physical\Weight;
use Drupal\profile\Entity\ProfileInterface;
use CommerceGuys\Addressing\Address;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\PackageType\PackageTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;

define('COMMERCE_CANADAPOST_LOGGER_CHANNEL', 'commerce_canadapost');

/**
 * Class CanadaPostUnitTestBase.
 *
 * @package Drupal\Tests\commerce_canadapost\Unit
 */
abstract class CanadaPostUnitTestBase extends UnitTestCase {

  /**
   * The Canada Post configuration object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The shipping method interface.
   *
   * @var \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface
   */
  protected $shippingMethod;

  /**
   * The shipment interface.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $shipment;

  /**
   * Set up requirements for test.
   */
  public function setUp() {
    parent::setUp();

    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('api.rate.origin_postal_code')
      ->willReturn('');
    $config->get('api.mode')
      ->willReturn('');
    $config->get('api.username')
      ->willReturn('mock_name');
    $config->get('api.password')
      ->willReturn('mock_pwd');
    $config->get('api.customer_number')
      ->willReturn('mock_cn');
    $config->get('api.contract_id')
      ->willReturn('');
    $config_factory->get('commerce_canadapost.settings')
      ->willReturn($config->reveal());

    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger = $this->prophesize(LoggerChannelInterface::class);
    $logger_factory->get(COMMERCE_CANADAPOST_LOGGER_CHANNEL)
      ->willReturn($logger->reveal());

    $this->configFactory = $config_factory->reveal();
    $this->loggerFactory = $logger_factory->reveal();

    $this->shippingMethod = $this->mockShippingMethod();
    $this->shipment = $this->mockShipment();
  }

  /**
   * Creates a mock Drupal Commerce shipment entity.
   *
   * @return \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   A mocked commerce shipment object.
   */
  public function mockShipment() {
    // Mock a Drupal Commerce Order and associated objects.
    $order = $this->prophesize(OrderInterface::class);
    $store = $this->prophesize(StoreInterface::class);

    // Mock the getAddress method to return a Canadian address.
    $store->getAddress()
      ->willReturn(new Address('CA', 'YK', 'Whitehorse', '', 'Y1A4P9', '', '9031 Quartz Road'));
    $order->getStore()->willReturn($store->reveal());

    // Mock a Drupal Commerce shipment and associated objects.
    $shipment = $this->prophesize(ShipmentInterface::class);
    $profile = $this->prophesize(ProfileInterface::class);
    $address_list = $this->prophesize(FieldItemListInterface::class);
    $address = $this->prophesize(AddressInterface::class);

    // Mock the address list to return a Canadian address.
    $address->getPostalCode()->willReturn('Y1A2C6');
    $address_list->first()->willReturn($address->reveal());
    $profile->get('address')->willReturn($address_list->reveal());
    $shipment->getShippingProfile()->willReturn($profile->reveal());
    $shipment->getOrder()->willReturn($order->reveal());

    // Mock the shipments weight.
    $shipment->getWeight()->willReturn(new Weight(1000, 'g'));

    // Return the mocked shipment object.
    return $shipment->reveal();
  }

  /**
   * Creates a mock Drupal Commerce shipping method.
   *
   * @return \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface
   *   The mocked shipping method.
   */
  public function mockShippingMethod() {
    $shipping_method = $this->prophesize(ShippingMethodInterface::class);
    $package_type = $this->prophesize(PackageTypeInterface::class);
    $package_type->getHeight()->willReturn(new Length(10, 'in'));
    $package_type->getLength()->willReturn(new Length(10, 'in'));
    $package_type->getWidth()->willReturn(new Length(3, 'in'));
    $package_type->getWeight()->willReturn(new Weight(10, 'lb'));
    $package_type->getRemoteId()->willReturn('custom');
    $shipping_method->getDefaultPackageType()->willReturn($package_type);
    $shipping_method->getConfiguration()->willReturn([
      'shipping_information' => [
        'origin_postal_code' => 'V1X5V1',
      ],
    ]);

    return $shipping_method->reveal();
  }

}
