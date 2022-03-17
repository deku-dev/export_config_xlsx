<?php

namespace Drupal\xlsx_config_export\Controller;

use Drupal\Core\Entity\EntityBase;

/**
 * Defines an interface for fields differently types.
 */
interface ExportContentInterface {

  /**
   * Get url image.
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Entity object.
   * @param string $fieldName
   *   Name field string.
   *
   * @return string
   *   Url image in server.
   */
  public function getImageUri(EntityBase $entity, string $fieldName);

  /**
   * Get text field without stats with striped html tags and line break.
   *
   * @param string $field
   *   Field texts with summary.
   *
   * @return string
   *   Text field.
   */
  public function getText(string $field);

  /**
   * Get taxonomy value name.
   *
   * @param string $tid
   *   Id taxonomy term.
   *
   * @return string
   *   String list taxonomy.
   */
  public function getTaxonomy(string $tid);

  /**
   * Convert timestamp to reading format.
   *
   * @return string
   *   Formatted string date.
   */
  public function getTime($field);

  /**
   * Get simply string from field without.
   *
   * @param string $field
   *   Name field with simply text.
   *
   * @return string
   *   Text string.
   */
  public function getSimplyString(string $field);

}
