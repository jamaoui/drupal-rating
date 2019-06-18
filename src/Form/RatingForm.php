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
use Drupal\rating\Services\RatingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an example form.
 */
class RatingForm extends ConfigFormBase {


  private $ratingService;

  public function __construct(ConfigFactoryInterface $config_factory, RatingService $ratingService, MessengerInterface $messenger) {
    parent::__construct($config_factory);

    $this->ratingService = $ratingService;
    $this->messenger = $messenger;
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

    $max_stars = $this->ratingService->getMaxStars();
    $stars_icon = $this->ratingService->getStarsIcons();
    $ratingDefaultKey = $this->ratingService->getRatingDefaultValue() - 1;

    $stars = [];
    for ($i = 1; $i <= $max_stars; $i++) {
      $multipleStars = '';
      for ($j = 0; $j < $i; $j++) {
        $multipleStars .= $stars_icon['full'];
      }
      $stars[(number_format(($i) / $max_stars, 2))] = $multipleStars;
    }

    // Test if the default value of the rating form stars is reverted for reversing the array and the stars order
    if ($this->ratingService->isInversedRatingForm()) {
      $stars = array_reverse($stars);
    }

    $form['value'] = [
      '#type' => 'radios',
      '#options' => $stars,
      '#default_value' => array_keys($stars)[$ratingDefaultKey],
      '#title' => $this->t('Your Vote'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Vote now'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['btn btn-primary btn-lg btn-lg'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('value');
    if ($value < 0 || $value > 1) {
      $form_state->setErrorByName('value', t('The vote value is not valid. Please enter valid value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $nid = $this->ratingService->getNodeId();

    $currentUser = $this->currentUser();

    $value = $form_state->getValue('value');

    if ($this->ratingService->checkNodeId($nid) !== NULL) {
      if (NULL === $this->ratingService->isUserRated($currentUser, $nid)) {
        $this->ratingService->setCurrentRating($currentUser, $nid, $value);
        $this->messenger->addMessage($this->t('Your vote is valid'));
      }
      else {
        $this->messenger->addError($this->t('You have already voted'));
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
      $container->get('messenger')
    );
  }
}
