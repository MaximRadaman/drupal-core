<?php

/**
 * @file
 * Contains \Drupal\config_translation\Routing\RouteSubscriber.
 */

namespace Drupal\config_translation\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * Constructs a new RouteSubscriber.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct(ConfigMapperManagerInterface $mapper_manager) {
    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection, $provider) {
    // @todo \Drupal\config_translation\ConfigNamesMapper uses the route
    //   provider directly, which is unsafe during rebuild. This currently only
    //   works by coincidence; fix in https://drupal.org/node/2158571.
    if ($provider != 'dynamic_routes') {
      return;
    }

    $mappers = $this->mapperManager->getMappers();
    foreach ($mappers as $mapper) {
      $collection->add($mapper->getOverviewRouteName(), $mapper->getOverviewRoute());
      $collection->add($mapper->getAddRouteName(), $mapper->getAddRoute());
      $collection->add($mapper->getEditRouteName(), $mapper->getEditRoute());
      $collection->add($mapper->getDeleteRouteName(), $mapper->getDeleteRoute());
    }
  }

}
