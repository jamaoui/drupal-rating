<?php

namespace Drupal\rating\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\rating\Interfaces\IRating;

/**
 * Class RatingService.
 */
class RatingService implements IRating {

  /*
   * @var \Drupal\Core\Database\Connection $database
   */
  protected $database;

  public $MAX_STARS = 5;

  public $configFactory;

  private $configData;

  const SETTINGS = 'rating.settings';

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $configFactory) {
    $this->database = $connection;
    $this->configFactory = $configFactory;
    //  Affect the config value of the max_stars
    $this->configData = $this->configFactory->get(static::SETTINGS)->get('');
  }


  public function checkNodeId($nid) {
    $query = $this->database->query("SELECT nid FROM {node} where nid={$nid}");
    $result = $query->fetchAssoc();
    return $result !== FALSE ? reset($result) : NULL;
  }

  public function getNodeId() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = NULL;
    if ($node instanceof NodeInterface) {
      $nid = $node->id();
    }
    return $nid;
  }

  public function getCurrentRating($nid) {
    $query = $this->database->query("SELECT count(nid) as 'count',avg(value) as 'avg' FROM {rating} where nid={$nid}");
    return $query->fetchAssoc();
  }

  public function isUserRated(AccountInterface $user, $nid) {
    $uid = $user->id();
    if ($nid === NULL) {
      return NULL;
    }
    $query = $this->database->query("SELECT nid FROM {rating} where uid={$uid} and nid={$nid}");
    $result = $query->fetchAssoc();
    return $result !== FALSE ? reset($result) : NULL;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $user
   * @param $nid
   * @param $value
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   */
  public function setCurrentRating(AccountInterface $user, $nid, $value) {
    $uid = $user->id();
    $email = $user->getEmail();

    if ($nid === NULL) {
      return NULL;
    }
    try {
      return $this->database->insert('rating')->fields([
        'rid',
        'email',
        'uid',
        'nid',
        'value',
      ], [
          NULL,
          $email,
          $uid,
          $nid,
          $value,
        ]
      )->execute();
    } catch (\Exception $e) {

    }
    return NULL;
  }

  public function deleteNodeRating($nid) {
    if ($nid === NULL) {
      return NULL;
    }
    try {
      return $this->database->delete('rating')
        ->condition('nid', $nid)
        ->execute();
    } catch (\Exception $e) {
      //die();
    }

  }

  public function getStarsIcons() {
    return [
      "full" => $this->configData['icon_star_full'],
      "half" => $this->configData['icon_star_half'],
      "empty" => $this->configData['icon_star_empty'],
    ];
  }

  /**
   * @return int
   */
  public function getMaxStars() {
    return (int) $this->configData['max_stars'];
  }

  /**
   * @return bool
   */
  public function isInversedRatingForm() {
    return (bool) $this->configData['inverse_showing_order'];
  }

  /**
   * @return int
   */
  public function getRatingDefaultValue() {
    $rdv = (int) $this->configData['rating_default_value'];

    if ($rdv <= $this->getMaxStars()) {
      return $rdv;
    }

    return 1;
  }

  /**
   * @return int
   */
  public function isDisplayedProgressBarForm() {
    return (bool) $this->configData['display_progress_bar'];
  }

  /**
   * @return array
   */
  public function getProgressBarPrimaryBgColors() {
    return [
      'primary' => $this->configData['progress_bar_primary_bgcolor'],
      'secondary' => $this->configData['progress_bar_secondary_bgcolor'],
    ];
  }

  /**
   * @return string
   */
  public function getProgressBarExtraText() {
    return $this->configData['progress_bar_extra_text'];
  }

}