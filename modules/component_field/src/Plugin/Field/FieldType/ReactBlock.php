<?php

namespace Drupal\react_block\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the 'react_block' field type.
 *
 * @FieldType(
 *   id = "react_block",
 *   label = @Translation("React block"),
 *   description = @Translation("React component printed as entity field."),
 *   category = @Translation("Reference"),
 *   default_widget = "react_block_options",
 *   default_formatter = "text_default",
 * )
 */
class ReactBlock extends FieldItemBase implements OptionsProviderInterface {

  private $options = NULL;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('React block'));
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
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getOptions();
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
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleOptions();
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions() {
    if (!is_null($this->options)) {
      return $this->options;
    }

    $this->options = [];

    $components = \Drupal::service('pdb.component_discovery')->getComponents();
    foreach ($components as $block_id => $block_info) {
      $this->options[$block_id] = $block_info->info['name'];
    }

    return $this->options;
  }

}
