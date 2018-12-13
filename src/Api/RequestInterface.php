<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Interface for the Canada Post API Service.
 *
 * @package Drupal\commerce_canadapost
 */
interface RequestInterface {

  /**
   * Fetch the Canada Post API settings, first from the store then the site.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   *
   * @return array
   *   Returns the api settings.
   */
  public function setApiSettings(StoreInterface $store = NULL);

  /**
   * Returns a Canada Post config to pass to the request service api.
   *
   * @return \CanadaPost\Rating
   *   The Canada Post request service object.
   */
  public function getRequestConfig();

  /**
   * Return the Canada Post API keys.
   *
   * @return array
   *   An array of API setting keys.
   */
  public function getApiKeys();

}
