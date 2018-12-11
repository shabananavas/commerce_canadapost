<?php

namespace Drupal\commerce_canadapost\Commands;

use Drupal\commerce_canadapost\Api\RatingServiceInterface;
use Drupal\commerce_canadapost\Api\TrackingServiceInterface;
use Drush\Commands\DrushCommands;
use function explode;
use function implode;

/**
 * Drush commands for the Commerce Canada Post module.
 */
class Commands extends DrushCommands {

  /**
   * The Tracking API service.
   *
   * @var \Drupal\commerce_canadapost\Api\TrackingServiceInterface
   */
  protected $trackingApi;

  /**
   * Constructs a new Commands object.
   *
   * @param \Drupal\commerce_canadapost\Api\TrackingServiceInterface $tracking_api
   *   The Tracking API service.
   */
  public function __construct(RatingServiceInterface $service_api, TrackingServiceInterface $tracking_api) {
    $this->trackingApi = $tracking_api;
  }

  /**
   * Fetching tracking summary for shipments and update the tracking data.
   *
   * @command commerce_canadapost:update_tracking
   * @aliases cc-uptracking
   * @option order_ids A comma-separated list of order IDs to update.
   * @usage commerce_canadapost:update_tracking
   *   Update tracking for all incomplete orders.
   * @usage commerce_canadapost:update_tracking --order_ids='1,2,3'
   *   Update tracking for order IDs 1,2,3.
   */
  public function updateTracking($options = ['order_ids' => NULL]) {
    $order_ids = NULL;
    if (!empty($options['order_ids'])) {
      $order_ids = explode(',', $options['order_ids']);
    }

    // Update the tracking.
    $updated_order_ids = _commerce_canadapost_update_tracking_data($order_ids);

    $this->logger()->success(dt(
      'Updated tracking for the following orders: @order_ids.', [
        '@order_ids' => implode(', ', $updated_order_ids),
      ]
    ));
  }

}
