<?php

namespace Drupal\weather_api\Service;

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
   * The OpenWeatherMap API key.
   *
   * @var string
   */
  protected string $apiKey;

  /**
   * Constructs the WeatherApiService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->apiKey = $_ENV['OPENWEATHER_API_KEY'];
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
    try {
      $url = 'https://api.openweathermap.org/data/2.5/weather';
      $params = [
        'q' => $city,
        'appid' => $this->apiKey,
      ];

      $response = $this->httpClient->get($url, ['query' => $params]);

      $data = json_decode($response->getBody(), TRUE);

      $formatted_weather_data = $this->formatWeatherData($data);

      $this->saveWeatherData($formatted_weather_data);

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
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('weather_api')->error('Failed to save weather data: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }
}
