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

final class LocationResult
{
    public function __construct(
        private string $id,
        private string $name,
        private string $countryCode,
        private string $countryName = '',
        private string $admin1 = '',
        private string $admin2 = '',
        private ?float $latitude = null,
        private ?float $longitude = null,
        private string $timezone = '',
        private string $featureCode = '',
        private ?int $population = null
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getCountryName(): string
    {
        return $this->countryName;
    }

    public function getAdmin1(): string
    {
        return $this->admin1;
    }

    public function getAdmin2(): string
    {
        return $this->admin2;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getFeatureCode(): string
    {
        return $this->featureCode;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function getLabel(): string
    {
        $parts = [$this->name];

        if ($this->admin1 !== '' && strcasecmp($this->admin1, $this->name) !== 0) {
            $parts[] = $this->admin1;
        }

        $country = $this->countryName !== '' ? $this->countryName : $this->countryCode;
        if ($country !== '') {
            $parts[] = $country;
        }

        return implode(' — ', array_values(array_unique($parts)));
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->getLabel(),
            'country_code' => $this->countryCode,
            'country' => $this->countryName,
            'admin1' => $this->admin1,
            'admin2' => $this->admin2,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'feature_code' => $this->featureCode,
            'population' => $this->population,
        ];
    }
}
