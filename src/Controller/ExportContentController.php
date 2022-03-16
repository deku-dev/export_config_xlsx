<?php

namespace Drupal\xlsx_config_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Session\AccountProxyInterface;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;


/**
 * Class ExportContentController.
 */
class ExportContentController extends ControllerBase {

  /**
   * Current worksheet in spreadsheet.
   */
  protected $worksheet;

  /*
   * Created spreadsheet.
   */
  protected $spreadsheet;

  /**
   * Current user object.
   *
   * @var AccountProxy
   */
  protected $currentUser;

  /**
   * Node for which to enter the config.
   *
   * @var NodeInterface
   */
  protected $node;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *  Current user data.
   */
  public function __construct(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritDoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\xlsx_config_export\Controller\ExportContentController|static
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
    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set('Expires', '0');
    $response->headers->set('Content-Type', 'application/vnd.ms-excel');
    $response->headers->set('Content-Disposition', 'attachment; filename=demo.xlsx');

    $this->settingSpreadsheet();

    $this->uploadDataCell();

    $response->setContent($this->saveTable());
    return $response;
  }

  /**
   * First setting parameter table.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  private function settingSpreadsheet() {

    // Create spreadsheet.
    $this->spreadsheet = new Spreadsheet();

    //Set metadata.
    $this->spreadsheet->getProperties()
      ->setCreator($this->currentUser->getAccountName())
      ->setLastModifiedBy($this->currentUser->getAccountName())
      ->setTitle($this->node->getTitle());

    $this->spreadsheet->setActiveSheetIndex(0);
    $this->worksheet = $this->spreadsheet->getActiveSheet();

    // Set size page.
    $this->worksheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
    $this->worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

    //Rename sheet
    $this->worksheet->setTitle($this->node->getTitle());
  }

  /**
   * Function add data and styling cell table.
   */
  protected function uploadDataCell() {
    /*
    * TITLE
    */
    //Set style Title
    $styleArrayTitle = array(
      'font' => array(
        'bold' => true,
        'color' => array('rgb' => '161617'),
        'size' => 12,
        'name' => 'Verdana'
      ));

    $this->worksheet->getCell('A1')->setValue($this->node->getTitle());
    $this->worksheet->getStyle('A1')->applyFromArray($styleArrayTitle);

    /*
     * HEADER
     */
    //Set Background
    $this->worksheet->getStyle('A3:E3')
      ->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()
      ->setARGB('085efd');

    //Set style Head
    $styleArrayHead = array(
      'font' => array(
        'bold' => true,
        'color' => array('rgb' => 'ffffff'),
      ));

    $this->worksheet->getCell('A3')->setValue('C1');
    $this->worksheet->getCell('B3')->setValue('C2');
    $this->worksheet->getCell('C3')->setValue('C3');

    $this->worksheet->getStyle('A3:E3')->applyFromArray($styleArrayHead);

    for ($i = 4; $i < 10; $i++) {
      $this->worksheet->setCellValue('A' . $i, $i);
      $this->worksheet->setCellValue('B' . $i, 'Test C2');
      $this->worksheet->setCellValue('C' . $i, 'Test C3');
    }

    // This inserts the SUM() formula with some styling.
    $this->worksheet->setCellValue('A10', '=SUM(A4:A9)');
    $this->worksheet->getStyle('A10')
      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $this->worksheet->getStyle('A10')
      ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);

    // This inserts the formula as text.
    $this->worksheet->setCellValueExplicit(
      'A11',
      '=SUM(A4:A9)',
      DataType::TYPE_STRING
    );
  }

  /**
   * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
   */
  protected function saveTable(){
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
   * Get the Image URI.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   * @param $fieldName
   * @param bool $imageStyle
   * @return mixed
   */
  public function getImageUri(\Drupal\Core\Entity\Entity $entity, $fieldName, $imageStyle = FALSE) {
    $imageField = $entity->get($fieldName)->getValue();
    if (!empty($imageField[0]['target_id'])) {
      $file = File::load($imageField[0]['target_id']);
      if ($imageStyle) {
        return ImageStyle::load($imageStyle)->buildUrl($file->getFileUri());
      }

      // Original URI.
      return file_create_url($file->getFileUri());
    }
  }

}
