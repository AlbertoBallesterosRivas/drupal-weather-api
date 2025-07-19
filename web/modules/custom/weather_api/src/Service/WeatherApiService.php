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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

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
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the API key from environment variable.
   */
  private function getApiKey(): ?string {
    $env_key = $_ENV['OPENWEATHER_API_KEY'];

    $this->loggerFactory->get('weather_api')->info('Debug - ENV key: @env', [
      '@env' => $env_key ? 'Found' : 'Not found',
    ]);

    if (!empty($env_key)) {
      return $env_key;
    }
    
    return NULL;
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
      $api_key = $this->getApiKey();
      
      if (empty($api_key)) {
        $this->loggerFactory->get('weather_api')->error('API key not configured.');
        return NULL;
      }

      $url = 'https://api.openweathermap.org/data/2.5/weather';
      $params = [
        'q' => $city,
        'appid' => $api_key,
        'units' => 'metric',
      ];

      $response = $this->httpClient->get($url, ['query' => $params]);
      $data = json_decode($response->getBody(), TRUE);
      
      if ($data && isset($data['main'])) {
        return $data;
      }
      
      return NULL;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('weather_api')->error('API request failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('weather_api')->error('Unexpected error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}