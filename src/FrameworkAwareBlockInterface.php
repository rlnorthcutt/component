<?php

namespace Drupal\component;

use Drupal\Core\Block\BlockPluginInterface;

/**
 * Defines the interface for block plugins which expose a front-end component.
 */
interface FrameworkAwareBlockInterface extends BlockPluginInterface {

  /**
   * Attaches the framework required by the component.
   *
   * @param array $component
   *   The component definition.
   *
   * @return array
   *   Array of attachments.
   */
  public function attachFramework(array $component);

  /**
   * Attaches JavaScript settings required by the component.
   *
   * @param array $component
   *   The component definition.
   *
   * @return array
   *   Array of attachments.
   */
  public function attachSettings(array $component);

  /**
   * Attaches any libraries required by the component.
   *
   * @param array $component
   *   The component definition.
   *
   * @return array
   *   Array of attachments.
   */
  public function attachLibraries(array $component);

  /**
   * Attaches anything the component needs in the HTML <head>.
   *
   * @param array $component
   *   The component definition.
   *
   * @return array
   *   Array of attachments.
   */
  public function attachPageHeader(array $component);

}
