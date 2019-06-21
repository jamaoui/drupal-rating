<?php
/**
 * @file
 * Contains \Drupal\rating\Form\RatingForm.
 */

namespace Drupal\rating\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rating\Services\RatingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an Rating Form
 */
class RatingForm extends ConfigFormBase {

  private $ratingService;

  private $currentUser;

  public function __construct(ConfigFactoryInterface $config_factory, RatingService $ratingService, MessengerInterface $messenger, AccountInterface $currentUser) {
    parent::__construct($config_factory);

    $this->ratingService = $ratingService;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'rating_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $ratingService = $this->ratingService;
    $max_stars = $ratingService->getMaxStars();
    $stars_icon = $ratingService->getStarsIcons();
    $ratingDefaultKey = $ratingService->getRatingDefaultValue() - 1;
    $canDeleteRating = $ratingService->canDeleteRating();
    $canUpdateRating = $ratingService->canUpdateRating();
    $nid = $ratingService->getNodeId();
    $isUserRated = $ratingService->isUserRated($this->currentUser, $nid);

    $stars = [];
    for ($i = 0; $i < $max_stars; $i++) {
      $multipleStars = $stars_icon['full'];
      for ($j = 0; $j < $i; $j++) {
        $multipleStars .= $stars_icon['full'];
      }
      for ($k = $i + 1; $k < $max_stars; $k++) {
        $multipleStars .= $stars_icon['empty'];
      }
      $stars[(number_format(($i + 1) / $max_stars, 2))] = $multipleStars;
    }

    $userRatingKey = NULL;
    if ($isUserRated !== FALSE) {
      $isUserRated = (float) number_format($isUserRated, 2);
      if ($isUserRated != 0) {
        foreach ($stars as $key => $star) {
          if ($key < $isUserRated) {
            continue;
          }
          $userRatingKey = $key;
          break;
        }
      }
    }

    // Test if the default value of the rating form stars is reverted for reversing the array and the stars order
    if ($ratingService->isInversedRatingForm()) {
      $stars = array_reverse($stars);
    }

    // Test the default value of the stars form
    if (isset($userRatingKey)) {
      $ratingDefaultKey = $userRatingKey;
      if (!$canUpdateRating) {
        $stars = [$ratingDefaultKey => $stars[$ratingDefaultKey]];
        $ratingDefaultKey = $ratingDefaultKey;
      }
    }
    else {
      if (isset($ratingDefaultKey)) {
        $keys = array_keys($stars);
        $ratingDefaultKey = (isset($keys[$ratingDefaultKey])) ? $keys[$ratingDefaultKey] : NULL;
      }
    }
    $form['value'] = [
      '#type' => 'hidden',
      //'#options' => $stars,
      //'#default_value' => $ratingDefaultKey,
      '#title' => $this->t('Your Vote'),
      '#min' => 0,
      '#max' => 1,
      '#step' => 'any',
      '#suffix' => '<div class="rating-stars-container"></div>',
      '#attributes' => [
        'class' => [
          'form-rating-range',
        ],
        'step' => [1 / $max_stars],
      ],
    ];


    $form['actions']['#type'] = 'actions';
    if ($isUserRated === FALSE) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Vote now'),
        '#button_type' => 'primary',
        '#attributes' => [
          'class' => ['btn d-none'],
        ],
      ];
    }
    else {
      if ($ratingService->canUpdateRating()) {
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Update'),
          '#button_type' => 'primary',
          '#attributes' => [
            'class' => ['btn btn-info btn-lg d-none'],
          ],
        ];
      }
    }

    if ($canDeleteRating && $isUserRated !== FALSE) {
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete your vote'),
        '#button_type' => 'primary',
        '#attributes' => [
          'class' => ['btn btn-danger btn-lg'],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*$value = $form_state->getValue('value');
    if ($value < 0 || $value > 1) {
      $form_state->setErrorByName('value', t('The vote value is not valid. Please enter valid value.'));
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $ratingService = $this->ratingService;
    $nid = $ratingService->getNodeId();

    // Deleting
    if ($form_state->getValue('op') == $form_state->getValue('delete')) {
      // If the users can delete their rating
      if ($ratingService->canDeleteRating()) {
        $ratingService->deleteUserRating($this->currentUser, $ratingService->getNodeId());
      }

    }
    //  Upserting
    else {
      $value = $form_state->getValue('value');

      if ($ratingService->checkNodeId($nid) !== NULL) {
        if ($ratingService->isUserRated($this->currentUser, $nid) === FALSE) {
          $ratingService->insertCurrentRating($this->currentUser, $nid, $value);
          $this->messenger->addMessage($this->t('Your vote is valid'));
        }
        else {
          if ($ratingService->canUpdateRating()) {
            $ratingService->updateCurrentRating($this->currentUser, $nid, $value);
          }
          else {
            //TODO The user can't Update (LOG,BAN)
            $this->messenger->addError($this->t('You have already voted'));
          }
        }
      }
    }

  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'rating.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('rating'),
      $container->get('messenger'),
      $container->get('current_user')
    );
  }
}
