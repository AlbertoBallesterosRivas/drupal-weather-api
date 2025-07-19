<?php

namespace Drupal\weather_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\weather_api\Service\WeatherApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Returns weather data using WeatherApiService.
 */
class WeatherController extends ControllerBase {

  /**
   * The weather API service.
   *
   * @var \Drupal\weather_api\Service\WeatherApiService
   */
  protected WeatherApiService $weatherApiService;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a WeatherController object.
   *
   * @param \Drupal\weather_api\Service\WeatherApiService $weather_api_service
   *   The weather API service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    WeatherApiService $weather_api_service,
    Connection $database,
  ) {
    $this->weatherApiService = $weather_api_service;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('weather_api.weather_service'),
      $container->get('database'),
    );
  }

  /**
   * Returns weather info for the given city.
   *
   * @param string $city
   *   The city name.
   *
   * @return array
   *   Render array.
   */
  public function weatherPage(string $city): array {
    $data = $this->weatherApiService->getWeather($city);

    if ($data === NULL) {
      return [
        '#markup' => $this->t('Could not retrieve the weather information.'),
      ];
    }

    $recent_searches = $this->getRecentSearches();

    return [
      '#markup' => '<pre class="data">' . print_r($data, TRUE) . '</pre>' .
        '<pre class="recent">' . print_r($recent_searches, TRUE) . '</pre>',
    ];
  }

  /**
   * Gets recent searches from the custom table for the current user.
   *
   * @return array
   *   An associative array of recent search entries.
   */
  protected function getRecentSearches(): array {
    $uid = \Drupal::currentUser()->id();

    if (!$uid) {
      return [];
    }

    return $this->database->select('weather_data', 'w')
      ->fields('w', ['city', 'country', 'created'])
      ->condition('uid', $uid)
      ->orderBy('created', 'DESC')
      ->range(0, 5)
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

}
