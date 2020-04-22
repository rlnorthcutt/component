<?php

namespace Drupal\component_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the thumbnail field formatter.
 *
 * @FieldFormatter(
 *   id = "component_field_formatter",
 *   label = @Translation("Component"),
 *   field_types = {
 *     "component_field"
 *   }
 * )
 */
class ComponentFieldFormatter extends FormatterBase {

  const MODULE_NAME = 'component_field';

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // Get the markup from the block deriver.
      $blockManager = \Drupal::service('plugin.manager.block');
      $content = $blockManager->createInstance(self::MODULE_NAME . ':' . $item->value)->build();

      $elements[$delta] = $content;
    }

    return $elements;
  }

}
