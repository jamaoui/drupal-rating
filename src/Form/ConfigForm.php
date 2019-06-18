<?php
/**
 * @file
 * Contains \Drupal\rating\Form\RatingForm.
 */

namespace Drupal\rating\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

  /** @var string Config settings */
  const SETTINGS = 'rating.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rating_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $rating_default_value = $config->get('rating_default_value');
    $max_stars = $config->get('max_stars');

    $form['progress_bar'] = [
      '#type' => 'details',
      '#title' => t('Progress bar'),
      '#open' => FALSE,
    ];
    $form['progress_bar']['progress_bar_primary_bgcolor'] = [
      '#type' => 'color',
      '#title' => $this->t('Primary background color'),
      '#default_value' => $config->get('progress_bar_primary_bgcolor'),
    ];
    $form['progress_bar']['progress_bar_secondary_bgcolor'] = [
      '#type' => 'color',
      '#title' => $this->t('Secondary background color'),
      '#default_value' => $config->get('progress_bar_secondary_bgcolor'),
    ];
    $form['progress_bar']['display_progress_bar'] = [

      '#title' => $this->t('Display the progress Bar ?'),
      '#default_value' => $config->get('display_progress_bar'),
      '#type' => 'radios',
      '#options' => [
        0 => $this
          ->t('No'),
        1 => $this
          ->t('Yes'),
      ],

    ];
    $form['progress_bar']['progress_bar_extra_text'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t('Extras Text (HTML)'),
      '#default_value' => (isset($config->get('progress_bar_extra_text')['value']))?$config->get('progress_bar_extra_text')['value']:'',
    ];

    $form['icons'] = [
      '#type' => 'details',
      '#title' => t('Icons'),
      '#open' => FALSE,
    ];
    $form['icons']['icon_star_full'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full star icon'),
      '#default_value' => $config->get('icon_star_full'),
    ];

    $form['icons']['icon_star_half'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Half star icon'),
      '#default_value' => $config->get('icon_star_half'),
    ];

    $form['icons']['icon_star_empty'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icone Empty'),
      '#default_value' => $config->get('icon_star_empty'),
    ];
    $form['form'] = [
      '#type' => 'details',
      '#title' => t('Form'),
      '#open' => FALSE,
    ];
    $form['form']['max_stars'] = [
      '#type' => 'number',
      '#title' => $this->t('Stars Number'),
      '#min' => '2',
      '#max' => '5',
      '#default_value' => $max_stars,
    ];
    $form['form']['inverse_showing_order'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('Do you want to inverse the order of stars showing in rating form ?'),
      '#default_value' => $config->get('inverse_showing_order'),
      '#options' => [
        0 => $this
          ->t('No'),
        1 => $this
          ->t('Yes'),
      ],
    ];
    $stars = [];
    for ($i = 1; $i <= $max_stars; $i++) {
      $stars[$i] = $i;
    }

    $form['form']['rating_default_value'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('Wish star will be the default rating value ?'),
      '#default_value' => (isset($rating_default_value) && $rating_default_value <= $max_stars) ? $rating_default_value : $max_stars,
      '#options' => $stars,
    ];
    $form['users'] = [
      '#type' => 'details',
      '#title' => t('Users'),
      '#open' => FALSE,
    ];
    $form['users']['can_modify_rating'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('The users can modify their rating ?'),
      '#default_value' => $config->get('can_modify_rating'),
      '#options' => [
        0 => $this
          ->t('No'),
        1 => $this
          ->t('Yes'),
      ],
    ];
    $form['users']['can_delete_rating'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('The users can delete their rating ?'),
      '#default_value' => $config->get('can_delete_rating'),
      '#options' => [
        0 => $this
          ->t('No'),
        1 => $this
          ->t('Yes'),
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('max_stars', $form_state->getValue('max_stars'))
      ->set('icon_star_full', $form_state->getValue('icon_star_full'))
      ->set('icon_star_half', $form_state->getValue('icon_star_half'))
      ->set('icon_star_empty', $form_state->getValue('icon_star_empty'))
      ->set('inverse_showing_order', $form_state->getValue('inverse_showing_order'))
      ->set('rating_default_value', $form_state->getValue('rating_default_value'))
      ->set('can_modify_rating', $form_state->getValue('can_modify_rating'))
      ->set('can_delete_rating', $form_state->getValue('can_delete_rating'))
      ->set('display_progress_bar', $form_state->getValue('display_progress_bar'))
      ->set('progress_bar_primary_bgcolor', $form_state->getValue('progress_bar_primary_bgcolor'))
      ->set('progress_bar_secondary_bgcolor', $form_state->getValue('progress_bar_secondary_bgcolor'))
      ->set('progress_bar_extra_text', $form_state->getValue('progress_bar_extra_text'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}