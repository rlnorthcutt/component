<?php

namespace Drupal\component_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the 'component_field' field type.
 *
 * @FieldType(
 *   id = "component_field",
 *   label = @Translation("Component"),
 *   description = @Translation("A component printed as entity field."),
 *   category = @Translation("Reference"),
 *   default_widget = "component_field_options",
 *   default_formatter = "text_default",
 * )
 */
class ComponentField extends FieldItemBase implements OptionsProviderInterface {

  private $options = NULL;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Component field'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions() {
    $options = [];
    // Get a list of all components.
    $discovery = \Drupal::service('component.component_discovery');
    $components = $discovery->getComponents();
    // Check each component to see if it should be listed.
    foreach ($components as $id => $component) {
      $is_field = isset($component->info['enable_field']);
      $is_enabled = ($is_field) ? $component->info['enable_field'] : FALSE;
      if ($is_enabled) {
        $options[$id] = $component->info['name'];
      }
    }
    return $options;
  }

  /**
   * Public methods
   */

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getEmptyLabel() {
    return t('- None -');
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys($this->getOptions());
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return $this->getPossibleValues();
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleOptions();
  }


}
