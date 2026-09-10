<?php
/**
 * @package     xdecaro.Core
 * @subpackage  Location
 *
 * @copyright   Copyright (C) 2026 Luca De Caro
 * @license     GNU General Public License version 2 or later
 */

namespace xdecaro\Core\Location;

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use RuntimeException;
use Throwable;

final class OpenMeteoLocationProvider implements LocationProviderInterface
{
    private const DEFAULT_ENDPOINT = 'https://geocoding-api.open-meteo.com/v1/search';

    public function __construct(
        private string $endpoint = self::DEFAULT_ENDPOINT,
        private string $apiKey = ''
    ) {
        $this->endpoint = rtrim(trim($this->endpoint), '?');
    }

    public function searchCities(
        string $query,
        ?string $countryCode = null,
        string $language = 'en',
        int $limit = 20
    ): array {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $countryCode = strtoupper(trim((string) $countryCode));
        if ($countryCode !== '' && !preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new RuntimeException('Invalid ISO country code.');
        }

        $language = strtolower(substr(str_replace('_', '-', trim($language)), 0, 2));
        if (!preg_match('/^[a-z]{2}$/', $language)) {
            $language = 'en';
        }

        $limit = max(1, min(100, $limit));
        $params = [
            'name' => $query,
            'count' => $limit,
            'language' => $language,
            'format' => 'json',
        ];

        if ($countryCode !== '') {
            $params['countryCode'] = $countryCode;
        }

        if ($this->apiKey !== '') {
            $params['apikey'] = $this->apiKey;
        }

        $url = $this->endpoint . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        try {
            $response = HttpFactory::getHttp()->get($url, [], 8);
        } catch (Throwable $exception) {
            throw new RuntimeException('World location provider is temporarily unavailable.', 0, $exception);
        }

        if ((int) $response->code < 200 || (int) $response->code >= 300) {
            throw new RuntimeException('World location provider returned an unexpected response.');
        }

        $payload = json_decode((string) $response->body, true);
        if (!is_array($payload)) {
            throw new RuntimeException('World location provider returned invalid data.');
        }

        $results = [];
        foreach ((array) ($payload['results'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $featureCode = strtoupper(trim((string) ($row['feature_code'] ?? '')));
            if ($featureCode !== '' && !str_starts_with($featureCode, 'P')) {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $rowCountryCode = strtoupper(trim((string) ($row['country_code'] ?? '')));

            if ($id === '' || $name === '' || !preg_match('/^[A-Z]{2}$/', $rowCountryCode)) {
                continue;
            }

            $results[] = new LocationResult(
                $id,
                $name,
                $rowCountryCode,
                trim((string) ($row['country'] ?? '')),
                trim((string) ($row['admin1'] ?? '')),
                trim((string) ($row['admin2'] ?? '')),
                isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null,
                isset($row['longitude']) && is_numeric($row['longitude']) ? (float) $row['longitude'] : null,
                trim((string) ($row['timezone'] ?? '')),
                $featureCode,
                isset($row['population']) && is_numeric($row['population']) ? (int) $row['population'] : null
            );
        }

        return $results;
    }
}
