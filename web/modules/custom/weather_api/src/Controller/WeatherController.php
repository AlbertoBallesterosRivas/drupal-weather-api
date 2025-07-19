<?php

namespace Drupal\weather_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\weather_api\Service\WeatherApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns weather data using WeatherApiService.
 */
class WeatherController extends ControllerBase {

  /**
   * Weather API service.
   *
   * @var \Drupal\weather_api\Service\WeatherApiService
   */
  protected WeatherApiService $weatherApiService;

  /**
   * Constructs a WeatherController object.
   */
  public function __construct(WeatherApiService $weather_api_service) {
    $this->weatherApiService = $weather_api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('weather_api.service')
    );
  }

  /**
   * Returns weather info for Madrid.
   */
  public function weatherPage(): array {
    $data = $this->weatherApiService->getWeather('Madrid');

    if ($data === NULL) {
      return [
        '#markup' => $this->t('Could not retrieve the weather information.'),
      ];
    }

    return [
      '#markup' => '<pre>' . print_r($data, TRUE) . '</pre>',
    ];
  }

}
