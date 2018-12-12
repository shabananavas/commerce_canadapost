Commerce Canada Post
=================

Provides Canada Post shipping rates and tracking functionality for Drupal Commerce.

## Development Setup


1. Use [Composer](https://getcomposer.org/) to get Commerce Canada Post with all dependencies: `composer require drupal/commerce_canadapost`

2. Enable module.

3. Go to /admin/commerce/config/shipping-methods/add:
  - Select 'Canada Post' as the Plugin
  - Select a default package type
  - Select all the shipping services that should be enabled
  - Click on the Canada Post API settings link under 'API Authentication'
    - Enter the customer number, username, password and other optional config and save configuration.

## Updating Tracking Information


Tracking summary for each shipment on an order can be seen in the order view page.

To add the tracking code received from Canada Post to a shipment:

1. Go to /admin/commerce/orders/{COMMERCE_ORDER_ID}/shipments

2. Click on the 'Edit' button under the appropriate shipment

3. Enter the tracking code received from Canada Post in the 'Tracking code' field and save.

Once a shipment is updated with a tracking code, tracking summary is automatically updated via cron.
It can also be done via the drush command: `drush cc-uptracking`.
