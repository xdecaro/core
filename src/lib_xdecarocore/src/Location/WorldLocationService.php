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

final class WorldLocationService
{
    public function __construct(private ?LocationProviderInterface $provider = null)
    {
        $this->provider ??= new OpenMeteoLocationProvider();
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
