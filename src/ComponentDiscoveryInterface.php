<?php

namespace Drupal\component;

/**
 * Defines the interface for services which discover front-end components.
 */
interface ComponentDiscoveryInterface {

  /**
   * Find all available front-end components.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   The discovered components.
   */
  public function getComponents();

}
