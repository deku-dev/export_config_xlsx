<?php

namespace Drupal\xlsx_config_export\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExportContentTypes.
 */
class ExportContentTypes extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cache render service.
   *
   * @var \Drupal\Core\Routing\RouteBuilder
   */
  protected $routerBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->routerBuilder = $container->get('router.builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'xlsx_config_export.exportcontenttypes',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'export_content_types';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xlsx_config_export.exportcontenttypes');

    $existingContentTypeOptions = $this->getExistingContentTypes();

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#options' => $existingContentTypeOptions,
      '#title' => $this->t('Content types'),
      '#default_value' => $config->get('content_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save data.
    $this->config('xlsx_config_export.exportcontenttypes')
      ->set('content_types', $form_state->getValue('content_types'))
      ->save();
    // Clear cache.
    \Drupal::service("router.builder")->rebuild();
  }

  /**
   * Returns a list of all the content types currently installed.
   *
   * @return array
   *   An array of content types.
   */
  public function getExistingContentTypes() {
    $types = [];
    $contentTypes = [];
    try {
      $contentTypes = $this->entityTypeManager->getStorage('node_type')
        ->loadMultiple();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
    }
    foreach ($contentTypes as $contentType) {
      $types[$contentType->id()] = $contentType->label();
    }
    return $types;
  }

}
