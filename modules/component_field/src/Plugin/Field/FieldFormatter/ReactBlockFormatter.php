<?php

namespace Drupal\react_block\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the thumbnail field formatter.
 *
 * @FieldFormatter(
 *   id = "react_block_formatter",
 *   label = @Translation("React Block Formatter"),
 *   field_types = {
 *     "react_block"
 *   }
 * )
 */
class ReactBlockFormatter extends FormatterBase {

  const MODULE_NAME = 'react_block';

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
