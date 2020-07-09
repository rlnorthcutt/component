<?php

namespace Drupal\component\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
class ComponentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The component info array.
   *
   * @var array
   */
  protected $component;

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
    $this->component = $this->getComponentInfo();
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
    // Attach the css/js files as defined in the info.yml file.
    $build['#attached']['library'] = $this->getLibraryName();
    // Grab the template and build the render array.
    $build['#theme'] = $this->getThemeHook();
    $build['#html_template'] = $this->getTemplate();
    $build['#content_attributes'] =  $this->buildAttributes();
    // Add caching settings. These can be managed from the component.yml file.
    $build['#cache'] = $this->getCacheSettings();
    return $build;
  }

  /**
   * Returns the library name.
   *
   * @return array
   *   The library name. This is created by component_library_info_build().
   */
  protected function getLibraryName() {
    $show = isset($this->component['js']) || isset($this->component['js']);
    return ($show) ? ['component/' . $this->component['machine_name']] : [];
  }

  /**
   * Returns the HTML template path.
   *
   * @return string
   *   The path for the template. Defaults to index.htm
   */
  protected function getTemplate() {
    $file = $this->component['path'] . $this->component['template'];
    return (file_exists($file)) ? file_get_contents($file) : '';
  }

  /**
   * Returns the hook_theme name.
   *
   * @return string
   *   The hook_theme definition. Defaults to 'component_html'
   */
  protected function getThemeHook() {
    return (isset($this->component['theme'])) ? $this->component['theme'] : 'component_html';
  }

  /**
   * Returns the cache settings.
   *
   * @return array
   *   Array with cache settings. Defaults to ['max-age' => 0]
   */
  protected function getCacheSettings() {
    return $this->component['cache'];
  }

  /**
   * Returns the component definition.
   *
   * @return array
   *   The component definition.
   */
  protected function getComponentInfo() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['info'];
  }

  /**
   * Create the data attributes for the wrapper div wround the component.
   *
   * @return array
   *   A unique id, machine name as class, and all of the component
   *   configuration options from the CMS. We pass the component configuration
   *   into the DOM through the data attributes in the wrapper around the
   *   component HTML.
   */
  protected function buildAttributes() {
    $component = $this->component;
    // @TODO: This unique id and name should be set on the object and generated
    // once only. This will have a new UUID per render. Can we use an internal
    // drupal uuid that is already generated by the plugin instance?
    $uuid = \Drupal::service('uuid')->generate();
    // Add a unique ID and machine name as class.
    $attributes = [
      'id' => $component['machine_name'] . '-' . $uuid,
      'class' => $component['machine_name'],
    ];
    // Write the config as data attributes on the wrapper.
    $data = $this->getComponentConfig();
    foreach ($data as $key => $value) {
      // JSON encode arrays and use single quotes.
      $value = is_array($value) ? json_encode($value) : $value;
      $attributes['data-' . str_replace('_', '-', $key)] = $value;
    }
    return $attributes;
  }

  /**
   * Returns the component configuration data.
   *
   * @return array
   *   The component configuration data.
   */
  protected function getComponentConfig() {
    $data = [];
    // Add static config if it exists.
    $component = $this->component;
    if (isset($component['static_configuration'])) {
      $data = $component['static_configuration'];
    }
    // Add form config if it exists.
    if (isset($this->configuration['form_configuration'])) {
      $data += $this->configuration['form_configuration'];
    }
    // Simplify and clean up arrays. Remove blank values and keys.
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = array_values(array_filter($value));
      }
    }
    return $data;
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
   * @return array
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
        // @todo Create whitelist or blacklist of form api properties.
        $element["#$property_key"] = $property;
      }
      if (isset($defaults[$key])) {
        $element['#default_value'] = $defaults[$key];
      }
      $elements[$key] = $element;
    }
    return $elements;
  }

  /**
   * Investigate and refactor the methods below.
   *
   * @todo
   */

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

}
