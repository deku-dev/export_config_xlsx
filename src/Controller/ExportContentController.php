<?php

namespace Drupal\xlsx_config_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityBase;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Class ExportContentController.
 */
class ExportContentController extends ControllerBase implements ExportContentInterface {

  /**
   * Current worksheet in spreadsheet.
   *
   * @var object
   */
  protected $worksheet;

  /**
   * Created spreadsheet.
   *
   * @var object
   */
  protected $spreadsheet;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Node for which to enter the config.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user data.
   */
  public function __construct(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritDoc}
   */
  public function getImageUri(EntityBase $entity, string $fieldName) {

    $imageField = $entity->get($fieldName)->getValue();
    if (!empty($imageField[0]['target_id'])) {
      $file = File::load($imageField[0]['target_id']);
      // Original URI.
      return file_create_url($file->getFileUri());
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getText(string $field) {
    return str_replace(
      ["\n", "\r\n", "\r"],
      '',
      strip_tags($this->node->get($field)->value));
  }

  /**
   * {@inheritDoc}
   */
  public function getSimplyString(string $field) {
    return $this->node->get($field)->value;
  }

  /**
   * {@inheritDoc}
   */
  public function getTime($field) {
    return date("F j, Y, g:i a", $this->node->get($field)->getString());
  }

  /**
   * {@inheritDoc}
   */
  public function getTaxonomy(string $tid) {
    $list_tax = [];
    foreach (explode(', ', $tid) as $id) {
      $term = Term::load($id);
      $list_tax[] = $term->getName();
    }
    return implode(', ', $list_tax);
  }

  /**
   * {@inheritDoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return \Drupal\xlsx_config_export\Controller\ExportContentController|static
   *   Static container dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Build-content.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Table response file xlsx.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function build(NodeInterface $node) {

    $this->node = $node;
    $response = new Response();

    // Set response headers.
    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set('Expires', '0');
    $response->headers->set(
      'Content-Type',
      'application/vnd.ms-excel');
    $response->headers->set(
      'Content-Disposition',
      'attachment; filename=' .
        $this->node->getEntityTypeId() . '-' . $this->node->id() . '.xlsx'
    );

    $this->setupSpreadsheet();

    $this->uploadDataCell();

    $response->setContent($this->saveTable());
    return $response;
  }

  /**
   * First setting parameter table.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  private function setupSpreadsheet() {

    // Create spreadsheet.
    $this->spreadsheet = new Spreadsheet();

    // Set metadata.
    $this->spreadsheet->getProperties()
      ->setCreator($this->currentUser->getAccountName())
      ->setLastModifiedBy($this->currentUser->getAccountName())
      ->setTitle($this->cutStr($this->node->getTitle(), 30));

    $this->spreadsheet->setActiveSheetIndex(0);
    $this->worksheet = $this->spreadsheet->getActiveSheet();

    // Set size page.
    $this->worksheet
      ->getPageSetup()
      ->setPaperSize(PageSetup::PAPERSIZE_A4);
    $this->worksheet
      ->getPageSetup()
      ->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

    // Set size cells.
    $this->worksheet
      ->getColumnDimension('A')
      ->setAutoSize(TRUE);
    $this->worksheet->getColumnDimension('B')->setWidth('4.46', 'in');

    $this->worksheet->getRowDimension(5)->setRowHeight(-1);

    // Rename sheet.
    $this->worksheet
      ->setTitle($this->cutStr($this->node->getTitle(), 30));
  }

  /**
   * Function add data and styling cell table.
   */
  protected function uploadDataCell() {

    $col_field = 'A';
    $col_value = 'B';
    $cell_row = 1;
    foreach ($this->node->getFieldDefinitions() as $name => $value) {
      $result_data = $this->controllerFieldTypes($name, $value->getType());
      $this->worksheet->getRowDimension($cell_row)->setRowHeight(-1);

      if (!$result_data) {
        continue;
      }

      // Center text and wrap that.
      $this->worksheet
        ->getStyle($cell_row)
        ->getAlignment()
        ->setVertical('center');
      $this->worksheet->getStyle($cell_row)
        ->getAlignment()->setWrapText(TRUE);

      // Set value to cells field name and value field.
      $this->worksheet->getCell($col_field . $cell_row)->setValue($name);
      $this->worksheet->getCell($col_value . $cell_row)->setValue($result_data);

      // Add links to cells for field image.
      if ($value->getType() == 'image') {
        $this->worksheet
          ->getCell($col_value . $cell_row)
          ->getHyperlink()
          ->setUrl($result_data);
      }
      $cell_row++;
    }
  }

  /**
   * Save table to php output.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
   */
  protected function saveTable() {

    // Get the writer and export in memory.
    $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
    ob_start();
    $writer->save('php://output');
    $content = ob_get_clean();

    // Memory cleanup.
    $this->spreadsheet->disconnectWorksheets();
    unset($this->spreadsheet);
    return $content;
  }

  /**
   * The function acts as a router and directs data to the correct getter.
   *
   * To add new fields you just need a new case definition
   *  and create a function to get the values.
   *
   * @param string $field
   *   Field name.
   * @param string $field_type
   *   Field type.
   *
   * @return string
   *   String return field.
   */
  protected function controllerFieldTypes(string $field, string $field_type) {

    switch ($field_type) {
      case 'boolean':
        break;

      case 'string':
        return $this->getSimplyString($field);

      case 'created':
      case 'changed':
        return $this->getTime($field);

      case 'text_with_summary':
        return $this->getText($field);

      case 'image':
        return $this->getImageUri($this->node, $field);

      case 'entity_reference':
        if (strpos($this->node
          ->getFieldDefinition($field)
          ->getSetting('handler'),
            'taxonomy_term') !== FALSE) {
          return $this->getTaxonomy($this->node->get($field)->getString());
        }
        break;
    }
    return FALSE;
  }

  /**
   * Smart string trim.
   *
   * @param string $str
   *   Source string.
   * @param int $length
   *   Length result string.
   * @param string $end
   *   End result string.
   * @param string $charset
   *   Coding string.
   * @param string $token
   *   Truncate character.
   *
   * @return string
   *   Cut string for length.
   */
  public function cutStr(string $str, int $length = 100, string $end = ' …', string $charset = 'UTF-8', string $token = '~') {

    $str = strip_tags($str);
    if (mb_strlen($str, $charset) >= $length) {
      $wrap = wordwrap($str, $length, $token);
      $str_cut = mb_substr($wrap, 0, mb_strpos($wrap, $token, 0, $charset), $charset);
      return $str_cut . $end;
    }
    else {
      return $str;
    }
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function getDynamicTabTitle(NodeInterface $node) {
    return $this->t('Dynamic tab for @type', ['@type' => $node->bundle()]);
  }

}
