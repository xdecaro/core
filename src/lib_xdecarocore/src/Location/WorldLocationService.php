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

use Joomla\CMS\Component\ComponentHelper;

final class WorldLocationService
{
    public function __construct(private ?LocationProviderInterface $provider = null)
    {
        $this->provider ??= new OpenMeteoLocationProvider();
    }

    public static function fromJoomlaConfiguration(): self
    {
        $params = ComponentHelper::getParams('com_xdecarocore');
        $endpoint = trim((string) $params->get('location_provider_endpoint', ''));
        $apiKey = trim((string) $params->get('location_provider_api_key', ''));

        return new self(new OpenMeteoLocationProvider(
            $endpoint !== '' ? $endpoint : 'https://geocoding-api.open-meteo.com/v1/search',
            $apiKey
        ));
    }

    /**
     * Search cities/towns/villages worldwide. The provider result is normalized
     * to a stable public array contract suitable for AJAX consumers.
     */
    public function searchCities(
        string $query,
        ?string $countryCode = null,
        string $language = 'en',
        int $limit = 20
    ): array {
        $items = $this->provider->searchCities($query, $countryCode, $language, $limit);
        $seen = [];
        $result = [];

        foreach ($items as $item) {
            if (!$item instanceof LocationResult) {
                continue;
            }

            $key = $item->getId();
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $item->toArray();
        }

        return $result;
    }
}
