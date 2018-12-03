<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

/**
 * Class CanadaPostTrackingtTest.
 *
 * @coversDefaultClass \Drupal\commerce_canadapost\Api\TrackingService
 * @group commerce_canadapost
 */
class CanadaPostTrackingtTest extends CanadaPostUnitTestBase {

  /**
   * ::covers fetchTrackingSummary.
   */
  public function testFetchTrackingSummary() {
    $tracking_summary = $this->trackingService->fetchTrackingSummary('7023210039414604');

    // Test the parsed response.
    $this->assertArrayHasKey('actual-delivery-date', $tracking_summary);
    $this->assertArrayHasKey('attempted-date', $tracking_summary);
    $this->assertArrayHasKey('expected-delivery-date', $tracking_summary);
    $this->assertArrayHasKey('mailed-on-date', $tracking_summary);
  }

}
