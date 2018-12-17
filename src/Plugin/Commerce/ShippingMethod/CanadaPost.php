<?php

namespace Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_canadapost\Api\RatingServiceInterface;
use Drupal\commerce_canadapost\UtilitiesService;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;

use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use CanadaPost\Rating;

/**
 * Provides the Canada Post shipping method.
 *
 * @CommerceShippingMethod(
 *  id = "canadapost",
 *  label = @Translation("Canada Post"),
 *  services = {
 *    "DOM.EP" = @Translation("Expedited Parcel"),
 *    "DOM.RP" = @Translation("Regular Parcel"),
 *    "DOM.PC" = @Translation("Priority"),
 *    "DOM.XP" = @Translation("Xpresspost"),
 *    "DOM.XP.CERT" = @Translation("Xpresspost Certified"),
 *    "DOM.LIB" = @Translation("Library Materials"),
 *    "USA.EP" = @Translation("Expedited Parcel USA"),
 *    "USA.PW.ENV" = @Translation("Priority Worldwide Envelope USA"),
 *    "USA.PW.PAK" = @Translation("Priority Worldwide pak USA"),
 *    "USA.PW.PARCEL" = @Translation("Priority Worldwide Parcel USA"),
 *    "USA.SP.AIR" = @Translation("Small Packet USA Air"),
 *    "USA.TP" = @Translation("Tracked Packet – USA"),
 *    "USA.TP.LVM" = @Translation("Tracked Packet – USA (LVM) (large volume mailers)"),
 *    "USA.XP" = @Translation("Xpresspost USA"),
 *    "INT.XP" = @Translation("Xpresspost International"),
 *    "INT.IP.AIR" = @Translation("International Parcel Air"),
 *    "INT.IP.SURF" = @Translation("International Parcel Surface"),
 *    "INT.PW.ENV" = @Translation("Priority Worldwide Envelope Int’l"),
 *    "INT.PW.PAK" = @Translation("Priority Worldwide pak Int’l"),
 *    "INT.PW.PARCEL" = @Translation("Priority Worldwide parcel Int’l"),
 *    "INT.SP.AIR" = @Translation("Small Packet International Air"),
 *    "INT.SP.SURF" = @Translation("Small Packet International Surface"),
 *    "INT.TP" = @Translation("Tracked Packet – International"),
 *   }
 * )
 */
class CanadaPost extends ShippingMethodBase {

  /**
   * The Canada Post utilities service object.
   *
   * @var \Drupal\commerce_canadapost\UtilitiesService
   */
  protected $utilities;

  /**
   * The rating service.
   *
   * @var \Drupal\commerce_canadapost\Api\RatingServiceInterface
   */
  protected $ratingService;

  /**
   * Constructs a new CanadaPost object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   * @param \Drupal\commerce_canadapost\UtilitiesService $utilities
   *   The Canada Post utilities service object.
   * @param \Drupal\commerce_canadapost\Api\RatingServiceInterface $rating_service
   *   The Canada Post Rating service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PackageTypeManagerInterface $package_type_manager,
    UtilitiesService $utilities,
    RatingServiceInterface $rating_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager);

    $this->utilities = $utilities;
    $this->ratingService = $rating_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_package_type'),
      $container->get('commerce_canadapost.utilities_service'),
      $container->get('commerce_canadapost.rating_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api' => [
        'customer_number' => '',
        'username' => '',
        'password' => '',
        'contract_id' => '',
        'mode' => 'test',
        'log' => 'test',
      ],
      'shipping_information' => [
        'origin_postal_code' => '',
        'option_codes' => [],
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form += $this->utilities->buildApiForm(NULL, $this);

    // Make fields required only if the use_store_settings checkbox is
    // unchecked.
    $this->alterApiFormFields($form);

    $form['shipping_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Shipping rate modifications'),
      '#open' => TRUE,
    ];

    $form['shipping_information']['origin_postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Origin postal code'),
      '#default_value' => $this->configuration['shipping_information']['origin_postal_code'],
      '#description' => $this->t("Enter the postal code that your shipping rates will originate. If left empty, shipping rates will be rated from your store's postal code."),
      '#required' => TRUE,
    ];

    $form['shipping_information']['option_codes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Options Codes'),
      '#options' => Rating::getOptionCodes(),
      '#default_value' => $this->configuration['shipping_information']['option_codes'],
      '#description' => $this->t(
        "Select which options to add when calculating the shipping rates. <strong>NOTE:</strong> Some options conflict with each other (eg. PA18, PA19 and DNS), so be sure to check the logs if the rates fail to load on checkout as the Canada Post API can't currently handle the conflicts."
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    // Save the API settings.
    foreach ($this->utilities->getApiKeys() as $key) {
      // If the user has NOT opted to create settings for this shipping method,
      // we empty out the settings.
      if ($values['api']['use_store_settings'] == TRUE) {
        $this->configuration['api'][$key] = NULL;
      }
      // Else, we save the settings.
      else {
        $this->configuration['api'][$key] = $values['api'][$key];
      }
    }

    $this->configuration['shipping_information']['origin_postal_code'] = $values['shipping_information']['origin_postal_code'];
    // Remove the empty options codes.
    $this->configuration['shipping_information']['option_codes'] = array_diff($values['shipping_information']['option_codes'], ['0']);

    return parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Calculates rates for the given shipment.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   *
   * @return \Drupal\commerce_shipping\ShippingRate[]
   *   The rates.
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Only attempt to collect rates if an address exists on the shipment.
    if ($shipment->getShippingProfile()->get('address')->isEmpty()) {
      return [];
    }

    return $this->ratingService->getRates(
      $this,
      $shipment,
      [
        'option_codes' => $this->configuration['shipping_information']['option_codes'],
        'service_codes' => $this->configuration['services'],
      ]
    );
  }

  /**
   * Determine if we have the minimum information to connect to Canada Post.
   *
   * @return bool
   *   TRUE if there is enough information to connect, FALSE otherwise.
   */
  public function apiIsConfigured() {
    $api_information = $this->configuration['api'];

    return (
      !empty($api_information['username'])
      && !empty($api_information['password'])
      && !empty($api_information['customer_number'])
      && !empty($api_information['mode'])
    );
  }

  /**
   * Alter the Canada Post API settings form fields.
   *
   * @param array $form
   *   The form array.
   */
  protected function alterApiFormFields(array &$form) {
    // Fields should be visible only if the use_store_settings checkbox is
    // unchecked.
    $states = [
      'visible' => [
        ':input[name="plugin[0][target_plugin_configuration][canadapost][api][use_store_settings]"]' => [
          'checked' => FALSE,
        ],
      ],
      'required' => [
        ':input[name="plugin[0][target_plugin_configuration][canadapost][api][use_store_settings]"]' => [
          'checked' => FALSE,
        ],
      ],
    ];
    foreach ($this->utilities->getApiKeys() as $key) {
      $form['api'][$key]['#states'] = $states;
      $form['api'][$key]['#required'] = FALSE;
    }

    // Contract ID and Log are not required so remove it from the states as
    // well.
    unset($form['api']['contract_id']['#states']['required']);
    unset($form['api']['log']['#states']['required']);

    // Set the default value for the checkbox.
    $form['api']['use_store_settings']['#default_value'] = !$this->apiIsConfigured();
  }

}
