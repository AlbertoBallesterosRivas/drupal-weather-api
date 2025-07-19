<?php

namespace Drupal\weather_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Weather API settings.
 */
class WeatherSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['weather_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'weather_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('weather_api.settings');

    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Configuration'),
      '#collapsible' => FALSE,
    ];

    $form['api_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenWeatherMap API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Your OpenWeatherMap API key. You can get one at <a href="@url" target="_blank">OpenWeatherMap</a>.', [
        '@url' => 'https://openweathermap.org/api',
      ]),
      '#required' => TRUE,
    ];

    $form['api_settings']['default_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default City'),
      '#default_value' => $config->get('default_city'),
      '#description' => $this->t('Default city to show when no city is specified.'),
    ];

    $form['cache_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cache & Performance'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // $form['cache_settings']['cache_duration'] = [
    //   '#type' => 'number',
    //   '#title' => $this->t('Cache Duration (seconds)'),
    //   '#default_value' => $config->get('cache_duration') ?? 3600,
    //   '#description' => $this->t('How long to cache weather data. Default: 3600 seconds (1 hour).'),
    //   '#min' => 300,
    //   '#max' => 86400,
    // ];

    $form['cache_settings']['max_history_records'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum History Records'),
      '#default_value' => $config->get('max_history_records') ?? 100,
      '#description' => $this->t('Maximum number of weather history records to keep per user.'),
      '#min' => 10,
      '#max' => 1000,
    ];

    // $form['cache_settings']['max_data_age'] = [
    //   '#type' => 'number',
    //   '#title' => $this->t('Maximum Data Age (seconds)'),
    //   '#default_value' => $config->get('max_data_age') ?? 604800,
    //   '#description' => $this->t('Delete weather data older than this. Default: 604800 seconds (7 days).'),
    //   '#min' => 3600,
    //   '#max' => 2592000,
    // ];

    $form['ui_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User Interface'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['ui_settings']['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Auto-refresh Interval (milliseconds)'),
      '#default_value' => $config->get('refresh_interval') ?? 300000,
      '#description' => $this->t('How often to automatically refresh weather data. Default: 300000ms (5 minutes). Set to 0 to disable.'),
      '#min' => 0,
      '#max' => 3600000,
    ];

    $form['ui_settings']['geolocation_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Geolocation'),
      '#default_value' => $config->get('geolocation_enabled') ?? TRUE,
      '#description' => $this->t('Allow users to use their current location for weather data.'),
    ];

    $form['display_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['display_settings']['units'] = [
      '#type' => 'select',
      '#title' => $this->t('Temperature Units'),
      '#default_value' => $config->get('units') ?? 'metric',
      '#options' => [
        'metric' => $this->t('Metric (°C)'),
        'imperial' => $this->t('Imperial (°F)'),
        'kelvin' => $this->t('Kelvin (K)'),
      ],
      '#description' => $this->t('Default temperature unit to display.'),
    ];

    $form['display_settings']['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Weather Language'),
      '#default_value' => $config->get('language') ?? 'en',
      '#options' => [
        'en' => $this->t('English'),
        'es' => $this->t('Spanish'),
        'fr' => $this->t('French'),
        'de' => $this->t('German'),
        'it' => $this->t('Italian'),
        'pt' => $this->t('Portuguese'),
        'ru' => $this->t('Russian'),
        'ja' => $this->t('Japanese'),
        'zh_cn' => $this->t('Chinese Simplified'),
      ],
      '#description' => $this->t('Language for weather descriptions.'),
    ];

    $form['features'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled Features'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // $form['features']['charts'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Enable Charts'),
    //   '#default_value' => $config->get('features.charts') ?? TRUE,
    //   '#description' => $this->t('Show weather data charts and graphs.'),
    // ];

    $form['features']['history'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable History'),
      '#default_value' => $config->get('features.history') ?? TRUE,
      '#description' => $this->t('Keep and display weather search history.'),
    ];

    // $form['features']['forecasts'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Enable Forecasts'),
    //   '#default_value' => $config->get('features.forecasts') ?? TRUE,
    //   '#description' => $this->t('Show weather forecasts (requires additional API calls).'),
    // ];

    // $form['features']['alerts'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Enable Weather Alerts'),
    //   '#default_value' => $config->get('features.alerts') ?? TRUE,
    //   '#description' => $this->t('Display weather alerts and warnings when available.'),
    // ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $api_key = $form_state->getValue('api_key');
    
    // Validate API key format (OpenWeatherMap API keys are 32 characters)
    if (!empty($api_key) && strlen($api_key) !== 32) {
      $form_state->setErrorByName('api_key', $this->t('OpenWeatherMap API key should be 32 characters long.'));
    }

    // Validate that cache duration is reasonable
    // $cache_duration = $form_state->getValue('cache_duration');
    // if ($cache_duration < 300) {
    //   $form_state->setErrorByName('cache_duration', $this->t('Cache duration should be at least 5 minutes (300 seconds) to avoid excessive API calls.'));
    // }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('weather_api.settings');
    
    // Save all form values to configuration
    $config
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('default_city', $form_state->getValue('default_city'))
      // ->set('cache_duration', (int) $form_state->getValue('cache_duration'))
      ->set('max_history_records', (int) $form_state->getValue('max_history_records'))
      // ->set('max_data_age', (int) $form_state->getValue('max_data_age'))
      ->set('refresh_interval', (int) $form_state->getValue('refresh_interval'))
      ->set('geolocation_enabled', (bool) $form_state->getValue('geolocation_enabled'))
      ->set('units', $form_state->getValue('units'))
      ->set('language', $form_state->getValue('language'))
      // ->set('features.charts', (bool) $form_state->getValue('charts'))
      ->set('features.history', (bool) $form_state->getValue('history'))
      // ->set('features.forecasts', (bool) $form_state->getValue('forecasts'))
      // ->set('features.alerts', (bool) $form_state->getValue('alerts'))
      ->save();

    parent::submitForm($form, $form_state);
    
    $this->messenger()->addMessage($this->t('Weather API configuration has been saved.'));
  }

}