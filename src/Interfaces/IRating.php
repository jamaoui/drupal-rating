<?php

namespace Drupal\rating\Interfaces;

use Drupal\Core\Session\AccountInterface;

interface  IRating {

  public function deleteNodeRating($nid);

  public function insertCurrentRating(AccountInterface $user, $nid, $value);

  public function updateCurrentRating(AccountInterface $user, $nid, $value);

  public function isUserRated(AccountInterface $user, $nid);

  public function getCurrentRating($nid);

  public function getNodeId();

  public function checkNodeId($nid);

  public function getStarsIcons();

  public function getMaxStars();

  public function isInversedRatingForm();

  public function getRatingDefaultValue();
}