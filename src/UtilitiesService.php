<?php

namespace Drupal\commerce_canadapost;

use Drupal\commerce_canadapost\Api\Request;
use Drupal\commerce_canadapost\Api\TrackingServiceInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_store\Entity\StoreInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class UtilitiesService.
 *
 * Contains helper functions for the Canada Post module.
 *
 * @package Drupal\commerce_canadapost
 */
class UtilitiesService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Tracking API service.
   *
   * @var \Drupal\commerce_canadapost\Api\TrackingServiceInterface
   */
  protected $trackingApi;

  /**
   * The Canada Post Request API service.
   *
   * @var \Drupal\commerce_canadapost\Api\RequestInterface
   */
  protected $requestApi;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a UtilitiesService class.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\commerce_canadapost\Api\TrackingServiceInterface $tracking_api
   *   The Tracking API service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TrackingServiceInterface $tracking_api,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->trackingApi = $tracking_api;
    $this->requestApi = new Request();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Encode the Canada Post API settings values in a json object.
   *
   * @param array $values
   *   The form_state values with the Canada Post API settings.
   *
   * @return object
   *   The encoded json object.
   */
  public function encodeSettings(array $values) {
    $api_settings_values = [];

    foreach ($this->requestApi->getApiKeys() as $key) {
      $api_settings_values[$key] = $values[$key];
    }

    return json_encode($api_settings_values);
  }

  /**
   * Build the form fields for the Canada Post API settings.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   *
   * @return array
   *   An array of form fields.
   *
   * @see \Drupal\commerce_canadapost\Form::buildForm()
   * @see \commerce_canadapost_form_alter()
   */
  public function buildApiForm(StoreInterface $store = NULL) {
    $form = [];

    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('Canada Post API authentication'),
      '#open' => TRUE,
    ];

    // Display an option to use the sitewide settings or have specific
    // settings for this store.
    if ($store) {
      $store_settings_set = empty($store->get('canadapost_api_settings')
        ->getValue()[0]['value']);

      $form['api']['use_sitewide_settings'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Canada Post sitewide API settings'),
        '#description' => $this->t('The Canada Post @url will be used when fetching rates and tracking details.
          <br \><strong>Uncheck</strong> this box if you\'d like to use a different account when fetching rates and tracking details for orders from this store.', [
            '@url' => Link::fromTextAndUrl(
              $this->t('sitewide API settings'),
              Url::fromRoute('commerce_canadapost.settings_form')
            )->toString(),
          ]
        ),
        '#default_value' => $store_settings_set,
      ];
    }

    // Fetch the Canada Post API settings.
    $api_settings = $this->requestApi->getApiSettings($store);

    $form['api']['customer_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer number'),
      '#default_value' => $api_settings['customer_number'],
      '#required' => TRUE,
    ];
    $form['api']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $api_settings['username'],
      '#required' => TRUE,
    ];
    $form['api']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $api_settings['password'],
      '#required' => TRUE,
    ];
    $form['api']['contract_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contract ID'),
      '#default_value' => $api_settings['contract_id'],
    ];
    $form['api']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#default_value' => $api_settings['mode'],
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#required' => TRUE,
    ];
    $form['api']['log'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Log the following messages for debugging'),
      '#options' => [
        'request' => $this->t('API request messages'),
        'response' => $this->t('API response messages'),
      ],
      '#default_value' => $api_settings['log'],
    ];

    // Add a note about store specific settings if we are in the sitewide page.
    if (!$store) {
      $form['api']['note'] = [
        '#type' => 'item',
        '#markup' => $this->t('<strong>To configure Canada Post API settings per store, go to the @url, edit the store, and add the account details there.</strong>', [
          '@url' => Link::fromTextAndUrl(
            $this->t('store settings page'),
            Url::fromRoute('entity.commerce_store.collection')
          )->toString(),
        ]),
      ];
    }

    // Alter the fields if we're in the store form.
    if ($store) {
      $this->alterApiFormFields($form);
    }

    return $form;
  }

  /**
   * Update tracking data for all incomplete canadapost shipments.
   *
   * @param array $order_ids
   *   An array of order IDs to update the tracking data for. Leave empty to
   *   update all orders with incomplete shipments.
   *
   * @return array
   *   An array of order IDs for which the shipments were updated for.
   */
  public function updateTracking(array $order_ids = NULL) {
    $updated_order_ids = [];

    // Fetch shipments for tracking.
    $shipments = $this->fetchShipmentsForTracking($order_ids);

    foreach ($shipments as $shipment) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      // Fetch tracking summary.
      $tracking_summary = $this->trackingApi->fetchTrackingSummary($shipment->getTrackingCode(), $shipment);

      // Update the shipment fields with the tracking data.
      $updated_order_ids[] = $this->updateTrackingFields($shipment, $tracking_summary);
    }

    return $updated_order_ids;
  }

  /**
   * Hide the Canada Post tracking fields from the checkout form.
   *
   * @param array $form
   *   The form array.
   */
  public function hideTrackingFields(array &$form) {
    foreach ($form['shipping_information']['shipments'] as &$shipment) {
      $fields = [
        'canadapost_actual_delivery',
        'canadapost_attempted_delivery',
        'canadapost_expected_delivery',
        'canadapost_mailed_on',
        'canadapost_current_location',
      ];
      foreach ($fields as $field) {
        if (isset($shipment[$field])) {
          $shipment[$field]['#access'] = FALSE;
        }
      }
    }
  }

  /**
   * Fetch all incomplete canadapost shipments that have a tracking pin.
   *
   * @param array $order_ids
   *   Only fetch shipments of specific order IDs.
   *
   * @return array
   *   An array of shipment entities.
   */
  protected function fetchShipmentsForTracking(array $order_ids = NULL) {
    // Query the db for the incomplete shipments.
    $shipment_query = $this->entityTypeManager
      ->getStorage('commerce_shipment')
      ->getQuery();
    $shipment_query
      ->condition('type', 'canadapost')
      ->condition('state', 'completed', '!=')
      ->condition('tracking_code', NULL, 'IS NOT NULL');
    // If specific order IDs have been passed.
    if (!empty($order_ids)) {
      $shipment_query->condition('order_id', $order_ids, 'IN');
    }
    // Fetch the results.
    $shipment_ids = $shipment_query->execute();

    // Return the loaded shipment entities.
    return $this->entityTypeManager
      ->getStorage('commerce_shipment')
      ->loadMultiple($shipment_ids);
  }

  /**
   * Update the shipment fields with the tracking summary.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The commerce shipment.
   * @param array $tracking_summary
   *   The tracking summary from Canada Post.
   *
   * @return int
   *   The order ID for which the shipment was updated for.
   */
  protected function updateTrackingFields(ShipmentInterface $shipment, array $tracking_summary) {
    // Update the fields.
    if ($tracking_summary['actual-delivery-date'] != '') {
      $shipment->set('canadapost_actual_delivery', $tracking_summary['actual-delivery-date']);
    }

    if ($tracking_summary['attempted-date'] != '') {
      $shipment->set('canadapost_attempted_delivery', $tracking_summary['attempted-date']);
    }

    if ($tracking_summary['expected-delivery-date'] != '') {
      $shipment->set('canadapost_expected_delivery', $tracking_summary['expected-delivery-date']);
    }

    if ($tracking_summary['mailed-on-date'] != '') {
      $shipment->set('canadapost_mailed_on', $tracking_summary['mailed-on-date']);
    }

    if ($tracking_summary['event-location'] != '') {
      $shipment->set('canadapost_current_location', $tracking_summary['event-location']);
    }

    // Now, save the shipment.
    $shipment->save();

    // Return the order ID for this updated shipment.
    return $shipment->getOrderId();
  }

  /**
   * Alter the Canada Post API settings form fields if we're in the store form.
   *
   * @param array $form
   *   The form array.
   */
  protected function alterApiFormFields(array &$form) {
    // Fields should be visible only if the use_site_settings checkbox is
    // unchecked.
    $states = [
      'visible' => [
        ':input[name="use_sitewide_settings"]' => [
          'checked' => FALSE,
        ],
      ],
      'required' => [
        ':input[name="use_sitewide_settings"]' => [
          'checked' => FALSE,
        ],
      ],
    ];
    foreach ($this->requestApi->getApiKeys() as $key) {
      $form['api'][$key]['#states'] = $states;
      $form['api'][$key]['#required'] = FALSE;
    }

    // Contract ID and Log are not required so remove it from the states as
    // well.
    unset($form['api']['contract_id']['#states']['required']);
    unset($form['api']['log']['#states']['required']);
  }

}
