<?php

namespace Drupal\component;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Discovery service for front-end components provided by modules and themes.
 *
 * Components (anything whose info file 'type' is 'component') are treated as
 * Drupal extensions unto themselves.
 */
class ComponentDiscovery extends ExtensionDiscovery implements ComponentDiscoveryInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The info parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * ComponentDiscovery constructor.
   *
   * @param string $root
   *   The root directory of the Drupal installation.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser.
   */
  public function __construct($root, ModuleHandlerInterface $module_handler, InfoParserInterface $info_parser) {
    parent::__construct($root);
    $this->moduleHandler = $module_handler;
    $this->infoParser = $info_parser;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponents() {
    // Find component info.yml files.
    $components = $this->scan('component');
    // Set defaults for component info.
    $defaults = [
      'dependencies' => [],
      'description' => '',
      'package' => 'Component',
      'version' => NULL,
      'core' => '8.x',
      'core_version_requirement' => '^8 || ^9',
    ];
    // Process each component.
    foreach ($components as $key => $component) {
      // Read the info file.
      $component->info = $this->infoParser->parse($component->getPathname());
      // Set the defaults and add the path info.
      $components[$key]->info = array_merge($defaults, $component->info);
      $components[$key]->info['path'] = $component->subpath;
      // Remove if they have an unmet module dependency.
      if (isset($component->info['module'])) {
        $modulename = $component->info['module'];
        if (!\Drupal::moduleHandler()->moduleExists($modulename)) {
          unset($components[$key]);
        }
      }
    }
    // Register the components.
    $this->moduleHandler->alter('component_info', $components);

    return $components;
  }

}
