<?php

namespace Drupal\weather_api\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;

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
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory
  ) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->apiKey = $config_factory->get('weather_api.settings')->get('api_key');
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
      return json_decode($response->getBody(), TRUE);
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('weather_api')->error('API request failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
