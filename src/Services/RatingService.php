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

  public $MAX_STARS;

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
    $query = $this->database->query("SELECT value FROM {rating} where uid={$uid} and nid={$nid}");
    $result = $query->fetchAssoc();
    if (isset($result['value'])) {
      return (float) $result['value'];
    }
    return FALSE;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $user
   * @param $nid
   * @param $value
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   */
  public function insertCurrentRating(AccountInterface $user, $nid, $value) {
    $uid = $user->id();
    $email = $user->getEmail();

    if ($nid === NULL) {
      return NULL;
    }
    try {

      return $this->database->insert('rating')->fields(
        [
          'email' => $email,
          'uid' => $uid,
          'nid' => $nid,
          'value' => $value,
        ]
      )->execute();
    } catch (\Exception $e) {
      // TODO

    }
    return NULL;
  }

  public function updateCurrentRating(AccountInterface $user, $nid, $value) {
    $uid = $user->id();
    $email = $user->getEmail();

    if ($nid === NULL) {
      return NULL;
    }
    try {
      return $this->database->update('rating')->fields([
          'email' => $email,
          'uid' => $uid,
          'nid' => $nid,
          'value' => $value,
        ]
      )->condition('uid', $uid)->condition('nid', $nid)->execute();
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
   * @return bool
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
  public function getExtraText() {
    return $this->configData['extra_text'];
  }

  /**
   * @return bool
   */
  public function canDeleteRating() {
    return (bool) $this->configData['can_delete_rating'];
  }

  /**
   * @return bool
   */
  public function canUpdateRating() {
    return (bool) $this->configData['can_update_rating'];
  }

  public function deleteUserRating(AccountInterface $user, $nid) {
    $uid = $user->id();

    if ($nid === NULL) {
      return NULL;
    }
    try {
      return $this->database->delete('rating')
        ->condition('nid', $nid)
        ->condition('uid', $uid)
        ->execute();
    } catch (\Exception $e) {

    }
    return NULL;
  }

}