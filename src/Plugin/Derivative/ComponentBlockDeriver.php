<?php

namespace Drupal\component\Plugin\Derivative;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\component\ComponentDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deriver for component blocks.
 */
class ComponentBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The component discovery service.
   *
   * @var \Drupal\component\ComponentDiscoveryInterface
   */
  protected $componentDiscovery;

  /**
   * ComponentBlockDeriver constructor.
   *
   * @param \Drupal\component\ComponentDiscoveryInterface $component_discovery
   *   The component discovery service.
   */
  public function __construct(ComponentDiscoveryInterface $component_discovery) {
    $this->componentDiscovery = $component_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('component.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get a list of components.
    $components = $this->componentDiscovery->getComponents();
    // Just check the block type components.
    foreach ($components['block'] as $name => $component) {
      $this->derivatives[$name] = $base_plugin_definition;
      $this->derivatives[$name]['info'] = $component;
      $this->derivatives[$name]['admin_label'] = $component['name'];
      $this->derivatives[$name]['cache'] = $component['cache'];
      if (isset($component['contexts'])) {
        $this->derivatives[$name]['context'] = $this->createContexts($component['contexts']);
      }
    }
    return $this->derivatives;
  }

  /**
   * Creates the context definitions required by a block plugin.
   *
   * @param array $contexts
   *   Contexts as defined in component label.
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinition[]
   *   Array of context to be used by block module
   *
   * @todo where is this defined in block module
   */
  protected function createContexts(array $contexts) {
    $contexts_definitions = [];
    if (isset($contexts['entity'])) {
      // @todo Check entity type exists and fail!
      $contexts_definitions['entity'] = new ContextDefinition('entity:' . $contexts['entity']);
    }
    // @todo Dynamically handle unknown context definitions
    return $contexts_definitions;
  }

}
