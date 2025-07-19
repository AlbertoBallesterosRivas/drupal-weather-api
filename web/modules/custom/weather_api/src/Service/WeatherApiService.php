<?php

namespace Drupal\weather_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for weather API operations.
 */
class WeatherApiService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs the WeatherApiService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the API key from configuration.
   *
   * @return string|null
   *   The API key or NULL if not set.
   */
  protected function getApiKey(): ?string {
    $config = $this->configFactory->get('weather_api.settings');
    $api_key = $config->get('api_key');
    
    // Fallback to environment variable if not set in config
    if (empty($api_key)) {
      $api_key = $_ENV['OPENWEATHER_API_KEY'] ?? NULL;
    }
    
    return $api_key;
  }

  /**
   * Gets weather data by city name.
   *
   * @param string $city
   *   The city name.
   *
   * @return array|null
   *   Weather data array or NULL on failure.
   */
  public function getWeather(string $city): ?array {
    $api_key = $this->getApiKey();
    
    if (empty($api_key)) {
      $this->loggerFactory->get('weather_api')->error('No API key configured for Weather API.');
      return NULL;
    }

    try {
      $config = $this->configFactory->get('weather_api.settings');
      
      $url = 'https://api.openweathermap.org/data/2.5/weather';
      $params = [
        'q' => $city,
        'appid' => $api_key,
        'units' => $config->get('units') ?? 'metric',
        'lang' => $config->get('language') ?? 'en',
      ];

      $response = $this->httpClient->get($url, ['query' => $params]);

      $data = json_decode($response->getBody(), TRUE);

      if (!$data || !isset($data['name'])) {
        $this->loggerFactory->get('weather_api')->warning('Invalid API response for city: @city', [
          '@city' => $city,
        ]);
        return NULL;
      }

      $formatted_weather_data = $this->formatWeatherData($data);

      // Only save if history is enabled
      if ($config->get('features.history')) {
        $this->saveWeatherData($formatted_weather_data);
      }

      return $formatted_weather_data;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('weather_api')->error('API request failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Format weather data from API response.
   *
   * @param array $data
   *   Raw API response data.
   *
   * @return array
   *   Formatted weather data.
   */
  protected function formatWeatherData(array $data): array {
    return [
      'city' => $data['name'] ?? '',
      'country' => $data['sys']['country'] ?? NULL,
      'latitude' => $data['coord']['lat'] ?? NULL,
      'longitude' => $data['coord']['lon'] ?? NULL,
      'temperature' => $data['main']['temp'] ?? NULL,
      'feels_like' => $data['main']['feels_like'] ?? NULL,
      'humidity' => $data['main']['humidity'] ?? NULL,
      'pressure' => $data['main']['pressure'] ?? NULL,
      'wind_speed' => $data['wind']['speed'] ?? NULL,
      'wind_direction' => $data['wind']['deg'] ?? NULL,
      'condition' => $data['weather'][0]['description'] ?? '',
      'condition_code' => $data['weather'][0]['id'] ?? NULL,
      'icon' => $data['weather'][0]['icon'] ?? NULL,
      'visibility' => $data['visibility'] ?? NULL,
      'date' => $data['dt'] ?? time(),
      'created' => time(),
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * Save weather data to database.
   *
   * @param array $weather_data
   *   Weather data to save.
   */
  protected function saveWeatherData(array $weather_data): void {
    $connection = \Drupal::database();
    
    try {
      $connection->insert('weather_data')
        ->fields($weather_data)
        ->execute();
        
      $this->loggerFactory->get('weather_api')->info('Weather data saved for city: @city', [
        '@city' => $weather_data['city'],
      ]);
      
      // Clean up old records
      $this->cleanupOldRecords();
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('weather_api')->error('Failed to save weather data: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Clean up old weather records based on configuration.
   */
  protected function cleanupOldRecords(): void {
    $config = $this->configFactory->get('weather_api.settings');
    $max_age = $config->get('max_data_age') ?? 604800; // 7 days default
    $max_records = $config->get('max_history_records') ?? 100;
    
    $connection = \Drupal::database();
    
    try {
      // Delete records older than max_data_age
      $old_timestamp = time() - $max_age;
      $connection->delete('weather_data')
        ->condition('created', $old_timestamp, '<')
        ->execute();
      
      // Keep only the most recent records per user
      $uid = \Drupal::currentUser()->id();
      if ($uid) {
        $subquery = $connection->select('weather_data', 'wd')
          ->fields('wd', ['id'])
          ->condition('uid', $uid)
          ->orderBy('created', 'DESC')
          ->range($max_records, PHP_INT_MAX);
        
        $connection->delete('weather_data')
          ->condition('id', $subquery, 'IN')
          ->condition('uid', $uid)
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('weather_api')->error('Failed to cleanup old records: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}