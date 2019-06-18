<?php

/**
 * @file
 * Contains \Drupal\rating\Plugin\Block\RatingBlock.
 */

namespace Drupal\rating\Plugin\Block;

use Drupal\Core\block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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


  /**
   * RatingBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param RatingService $ratingService
   * @param FormBuilderInterface $formBuilder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RatingService $ratingService, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ratingService = $ratingService;
    $this->formBuilder = $formBuilder;
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
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Overrides \Drupal\Core\Block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['content'] = $form_state->getValue('content');
  }

  /**
   * Implements \Drupal\Core\Block\BlockBase::blockBuild().
   */
  public function build() {

    $RatingForm = $this->formBuilder->getForm('Drupal\rating\Form\RatingForm');
    // Get The Current Rating
    $currentRating = $this->ratingService->getCurrentRating($this->ratingService->getNodeId());
    if ($currentRating === NULL) {
      return [];
    }

    $data['count'] = $currentRating['count'];
    $data['avg'] = $currentRating['avg'];
    $data['MAX_STARS'] = $this->ratingService->getMaxStars();
    $data['nbStars'] = $data['avg'] * $data['MAX_STARS'];
    $data['IS_HALF_STAR'] = is_float($data['nbStars']);
    $data['rating_form'] = $RatingForm;
    $data['icons']['star'] = $this->ratingService->getStarsIcons();
    if ($this->ratingService->isDisplayedProgressBarForm()) {
      $data['progress_bar'] = [
        'avg'=>round($data['nbStars']/$data['MAX_STARS'],2)*100,
        'bg_color'=>$this->ratingService->getProgressBarPrimaryBgColors(),
        'extra_text'=>$this->ratingService->getProgressBarExtraText(),
      ];
    }

    return [
      '#theme' => 'block-rating',
      '#data' => $data,
      '#attached' => [
        'library' => [
          'rating/rating',
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('rating'),
      $container->get('form_builder')
    );
  }
}
