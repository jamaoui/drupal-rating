<?php

namespace Drupal\rating\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rating\Services\RatingService;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a 'Example: configurable text string' block.
 *
 * @Block(
 *   id = "rating_block",
 *   subject = @Translation("Rating"),
 *   admin_label = @Translation("Rating Block")
 * )
 */
class RatingBlock extends BlockBase implements ContainerFactoryPluginInterface {

  private $ratingService;

  private $formBuilder;

  private $currentUser;


  /**
   * RatingBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param RatingService $ratingService
   * @param FormBuilderInterface $formBuilder
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RatingService $ratingService, FormBuilderInterface $formBuilder, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ratingService = $ratingService;
    $this->formBuilder = $formBuilder;
    $this->currentUser = $currentUser;
  }

  /**
   * Overrides \Drupal\Core\Block\BlockBase::defaultConfiguration().
   */
  public function defaultConfiguration() {
    return [
      'label' => t('Rating'),
      'content' => t('Rating'),
    ];
  }

  /**
   * Overrides \Drupal\Core\Block\BlockBase::blockForm().
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Overrides \Drupal\Core\Block\BlockBase::blockSubmit().
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['content'] = $form_state->getValue('content');
  }

  /**
   * Implements \Drupal\Core\Block\BlockBase::blockBuild().
   */
  public function build() {

    $RatingForm = $this->formBuilder->getForm('Drupal\rating\Form\RatingForm');
    $RatingService = $this->ratingService;
    $isUserRated = $RatingService->isUserRated($this->currentUser, $RatingService->getNodeId());
    $canDeleteRating = $RatingService->canDeleteRating();
    $canUpdateRating = $RatingService->canUpdateRating();

    $RatingService = $this->ratingService;
    // Get The Current Rating
    $currentRating = $RatingService->getCurrentRating($RatingService->getNodeId());
    if ($currentRating === NULL) {
      return [];
    }

    $data['count'] = $currentRating['count'];
    $data['avg'] = $currentRating['avg'];
    $data['MAX_STARS'] = $RatingService->getMaxStars();
    $data['nbStars'] = $data['avg'] * $data['MAX_STARS'];
    $IS_HALF_STAR = $data['nbStars'] - floor($data['nbStars']) >= 0.5;
    if ($IS_HALF_STAR) {
      $data['IS_HALF_STAR'] = TRUE;
    }

    $data['rating_form'] = $RatingForm;

    $data['icons']['star'] = $RatingService->getStarsIcons();
    if ($RatingService->isDisplayedProgressBarForm()) {
      $data['progress_bar'] = [
        'avg' => round($data['nbStars'] / $data['MAX_STARS'], 2) * 100,
        'bg_color' => $RatingService->getProgressBarPrimaryBgColors(),
      ];
    }
    $data['extra_text'] = $RatingService->getExtraText();


    $PrimaryInput = $data['rating_form']['value'];
    $attachToJs = [
      'RatingBlock' => [
        'currentData' => [

        ],
        'form' => [
          'primaryInput' => [
            'name' => $PrimaryInput['#name'],
            'isInput' => $PrimaryInput['#input'],
            'type' => $PrimaryInput['#type'],
            'max' => $PrimaryInput['#max'],
            'step' => $PrimaryInput['#attributes']['step'],
            'min' => $PrimaryInput['#min'],
            'class' => $PrimaryInput['#attributes']['class'],
          ],

        ],
        'permissions' => [
          'update' => $canUpdateRating,
          'delete' => $canDeleteRating,
        ],
        'isUserRated' => $isUserRated,
        'count' => $data['count'],
        'MAX_STARS' => $data['MAX_STARS'],
        'nbStars' => $data['nbStars'],
        'IS_HALF_STAR' => $IS_HALF_STAR,
        'icons' => ['star' => $data['icons']['star']],
      ],
    ];

    return [
      '#theme' => 'block-rating',
      '#data' => $data,
      '#attached' => [
        'drupalSettings' => $attachToJs,
        'library' => [
          'rating/rating',
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('rating'),
      $container->get('form_builder'),
      $container->get('current_user')
    );
  }
}
