<?php

namespace Drupal\component\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\component\FrameworkAwareBlockInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ComponentBlock.
 *
 * @package Drupal\component\Plugin\Block
 */
abstract class ComponentBlock extends BlockBase implements FrameworkAwareBlockInterface, ContainerFactoryPluginInterface {

  /**
   * ComponentBlock constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $component = $this->getComponentInfo();
    $this->configuration['uuid'] = \Drupal::service('uuid')->generate();

    $attached = [];

    $framework = $this->attachFramework($component);
    if ($framework) {
      $attached = array_merge_recursive($attached, $framework);
    }

    $settings = $this->attachSettings($component);
    if ($settings) {
      $attached = array_merge_recursive($attached, $settings);
    }

    $libraries = $this->attachLibraries($component);
    if ($libraries) {
      $attached = array_merge_recursive($attached, $libraries);
    }

    $header = $this->attachPageHeader($component);
    if ($header) {
      $attached = array_merge_recursive($attached, $header);
    }

    if ($contexts = $this->getContexts()) {
      $attached['drupalSettings']['component']['contexts'] = $this->getJsContexts($contexts);
    }
    if (isset($this->configuration['component_configuration'])) {
      // @todo Is there anything else unique to key off of besides uuid
      $attached['drupalSettings']['component']['configuration'][$this->configuration['uuid']] = $this->configuration['component_configuration'];
    }
    return [
      '#attached' => $attached,
    ];
  }

  /**
   * Returns the component definition.
   *
   * @return array
   *   The component definition.
   */
  public function getComponentInfo() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['info'];
  }

  /**
   * {@inheritdoc}
   */
  public function attachFramework(array $component) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibraries(array $component) {
    // Attach the header and footer component library.
    $path                = 'component/' . $component['machine_name'];
    $component_libraries = [];

    if (isset($component['add_css']['header']) || isset($component['add_js']['header'])) {
      $component_libraries[] = $path . '/header';
    }

    if (isset($component['add_css']['footer']) || isset($component['add_js']['footer'])) {
      $component_libraries[] = $path . '/footer';
    }

    return $component_libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function attachSettings(array $component) {
    if (isset($component['settings'])) {
      return [
        'drupalSettings' => $component['settings'],
      ];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function attachPageHeader(array $component) {
    return [];
  }

  /**
   * Add serialized entity to the JS Contexts.
   *
   * @param \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $data
   *   The entity to serialize.
   * @param array $js_contexts
   *   The full array of JS contexts.
   * @param string $key
   *   The context key.
   */
  protected function addEntityJsContext(EntityAdapter $data, array &$js_contexts, $key) {
    $entity = $data->getValue();
    $entity_access = $entity->access('view', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      return;
    }
    foreach ($entity as $field_name => $field) {
      // @var \Drupal\Core\Field\FieldItemListInterface $field
      $field_access = $field->access('view', NULL, TRUE);

      // @todo Used addCacheableDependency($field_access);
      if (!$field_access->isAllowed()) {
        $entity->set($field_name, NULL);
      }
    }

    $js_contexts["$key:" . $entity->getEntityTypeId()] = $entity->toArray();
  }

  /**
   * Get an array of serialized JS contexts.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   The contexts to serialize.
   *
   * @return array
   *   An array of serialized JS contexts.
   */
  protected function getJsContexts(array $contexts) {
    $js_contexts = [];
    foreach ($contexts as $key => $context) {
      $data = $context->getContextData();
      if ($data instanceof EntityAdapter) {
        $this->addEntityJsContext($data, $js_contexts, $key);
      }
    }
    return $js_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['component_configuration'] = $this->buildComponentSettingsForm($form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['component_configuration'] = $form_state->getValue('component_configuration');
  }

  /**
   * Build settings component settings form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   */
  protected function buildComponentSettingsForm(FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();
    $elements = [];
    if (isset($definition['info']['configuration'])) {
      $elements = $this->createElementsFromConfiguration($definition['info']['configuration'], $form_state);
      $elements['#title'] = $this->t('Component Settings');
      $elements['#type'] = 'details';
      $elements['#open'] = TRUE;
      $elements['#tree'] = TRUE;
    }
    return $elements;
  }

  /**
   * Create Form API elements from component configuration.
   *
   * @param array $configuration
   *   The configuration array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   *
   * @return array
   *   Form elements.
   */
  protected function createElementsFromConfiguration(array $configuration, FormStateInterface $form_state) {
    $elements = [];
    $defaults = (!empty($this->configuration['component_configuration'])) ?
      $this->configuration['component_configuration'] : [];
    foreach ($configuration as $key => $setting) {
      $element = [];
      foreach ($setting as $property_key => $property) {
        // @todo Create whitelist or blacklist of form api properties
        $element["#$property_key"] = $property;
      }
      if (isset($defaults[$key])) {
        $element['#default_value'] = $defaults[$key];
      }
      $elements[$key] = $element;
    }
    return $elements;
  }

}
