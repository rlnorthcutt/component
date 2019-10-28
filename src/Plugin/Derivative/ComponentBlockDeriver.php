<?php

namespace Drupal\component\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
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
      $container->get('component.component_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get all custom blocks which should be rediscovered.
    $components = $this->componentDiscovery->getComponents();
    foreach ($components as $block_id => $block_info) {
      $this->derivatives[$block_id] = $base_plugin_definition;
      $this->derivatives[$block_id]['info'] = $block_info->info;
      $this->derivatives[$block_id]['admin_label'] = $block_info->info['name'];
      $this->derivatives[$block_id]['cache'] = array('max-age' => 0);
      if (isset($block_info->info['contexts'])) {
        $this->derivatives[$block_id]['context'] = $this->createContexts($block_info->info['contexts']);
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
