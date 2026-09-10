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

interface LocationProviderInterface
{
    /**
     * Search populated places worldwide.
     *
     * @return LocationResult[]
     */
    public function searchCities(
        string $query,
        ?string $countryCode = null,
        string $language = 'en',
        int $limit = 20
    ): array;
}
