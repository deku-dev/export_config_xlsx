<?php

namespace Drupal\xlsx_config_export\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the xlsx_config_export module.
 */
class ExportContentControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "xlsx_config_export ExportContentController's controller functionality",
      'description' => 'Test Unit for module xlsx_config_export and controller ExportContentController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests xlsx_config_export functionality.
   */
  public function testExportContentController() {
    // Check that the basic functions of module xlsx_config_export.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
