<?php

/**
 * @file
 * Contains \Drupal\rating\Controller\RatingRoutingController.
 */

namespace Drupal\rating\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\rating\Services\RatingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Events Demo module routes.
 */
class RatingRoutingController extends ControllerBase implements ContainerInjectionInterface {

  private $ratingService;

  /**
   * RatingRoutingController constructor.
   *
   * @param RatingService $ratingService
   */
  public function __construct(RatingService $ratingService) {
    $this->ratingService = $ratingService;

  }

  /**
   * Controller content callback: Hello page with dynamic URL.
   *
   * @param Request $request
   *
   * @return array
   */
  public function rate(Request $request) {
    $nid = $request->get("nid");

    $value = $request->get("value");

    $currentUser = $this->currentUser();

    $message = $this->t('Your vote is invalid');

    if ($this->ratingService->checkNodeId($nid) !== NULL) {

      if (NULL === $this->ratingService->isUserRated($currentUser, $nid)) {
        $message = $this->t('Your vote is valid');
        $this->ratingService->setCurrentRating($currentUser, $nid, $value);
      }
    }

    return [
      '#markup' => $message,
    ];
  }

  // AUTO WIRING

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rating')
    );
  }


}
