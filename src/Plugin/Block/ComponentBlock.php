<?php

namespace Drupal\component\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\component\FrameworkAwareBlockInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes a Component as a block.
 *
 * @Block(
 *   id = "component",
 *   admin_label = @Translation("Component"),
 *   deriver = "\Drupal\component\Plugin\Derivative\ComponentBlockDeriver"
 * )
 */

class ComponentBlock extends BlockBase implements FrameworkAwareBlockInterface, ContainerFactoryPluginInterface {

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
    $machine_name = $component['machine_name'];
    $content_config = $component['content_configuration'];

    $data_values = [];

    if (array_key_exists('block_data_output', $content_config)) {
      $data_values = $content_config['block_data_output'];
    }

    $markup = $this->buildMarkup($machine_name, $data_values);
    $build['#allowed_tags'] = ['div'];
    $build['#markup'] = $markup;

    $build['#cache'] = ['max-age' => 0];

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
      $attached_libraries = array_merge_recursive($attached, $libraries);
    }

    $header = $this->attachPageHeader($component);
    if ($header) {
      $attached = array_merge_recursive($attached, $header);
    }

    $build['#attached']['library'] = $attached_libraries;

    return $build;

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
   * Builds markup to be returned to block form api.
   *
   * @param string $machine_name
   *   The machine name of the block.
   * @param array $data_values
   *   The data values to be placed in html 5 data attribute.
   *
   * @return markup
   *   returns the markup.
   */
  private function buildMarkup($machine_name, array $data_values) {
    $uuid = \Drupal::service('uuid')->generate();
    $markup = '<div id="' . $machine_name .  $uuid . '" class="' . $machine_name . '" ';

    foreach ($data_values as $key => $value) {
      $markup .= 'data-' . str_replace('_', '-', $key) . '="' . $value . '" ';
    }

    $markup .='></div>';

    return $markup;
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
    $form['form_configuration'] = $this->buildComponentFormSettingsForm($form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['form_configuration'] = $form_state->getValue('form_configuration');
  }

  /**
   * Build settings form configuration settings form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   *
   *  @return array
   *   Form elements.
   */
  protected function buildComponentFormSettingsForm(FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();
    $elements = [];
    if (isset($definition['info']['form_configuration'])) {
      $elements = $this->createElementsFromFormConfiguration($definition['info']['form_configuration'], $form_state);
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
  protected function createElementsFromFormConfiguration(array $configuration, FormStateInterface $form_state) {
    $elements = [];
    $defaults = (!empty($this->configuration['form_configuration'])) ?
      $this->configuration['form_configuration'] : [];
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
