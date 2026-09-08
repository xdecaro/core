<?php
/**
 * @package     xdecaro.Core
 * @subpackage  Integration
 *
 * @copyright   Copyright (C) 2026 Luca De Caro
 * @license     GNU General Public License version 2 or later
 */

namespace xdecaro\Core\Integration;

defined('_JEXEC') or die;

use InvalidArgumentException;

final class CapabilityRegistry
{
    /** @var array<string,Capability> */
    private $capabilities = [];

    public function register(Capability $capability): void
    {
        $this->capabilities[$capability->key()] = $capability;
    }

    /** @param array<int,Capability> $capabilities */
    public function registerMany(array $capabilities): void
    {
        foreach ($capabilities as $capability) {
            if (!$capability instanceof Capability) {
                throw new InvalidArgumentException('CapabilityRegistry accepts only Capability instances.');
            }

            $this->register($capability);
        }
    }

    public function supports(string $component, string $name, string $minimumVersion = '1'): bool
    {
        $probe = new Capability($component, $name, $minimumVersion);

        foreach ($this->capabilities as $capability) {
            if ($capability->getComponent() !== $probe->getComponent()) {
                continue;
            }

            if ($capability->getName() !== $probe->getName()) {
                continue;
            }

            if (version_compare($capability->getVersion(), $probe->getVersion(), '>=')) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int,Capability> */
    public function all(): array
    {
        $capabilities = array_values($this->capabilities);

        usort($capabilities, static function (Capability $left, Capability $right): int {
            return strcmp($left->key(), $right->key());
        });

        return $capabilities;
    }

    /** @return array<int,Capability> */
    public function forComponent(string $component): array
    {
        $component = (new Capability($component, 'core.probe', '1'))->getComponent();

        return array_values(array_filter(
            $this->all(),
            static function (Capability $capability) use ($component): bool {
                return $capability->getComponent() === $component;
            }
        ));
    }

    public function isEmpty(): bool
    {
        return $this->capabilities === [];
    }

    public function count(): int
    {
        return count($this->capabilities);
    }

    public function clear(): void
    {
        $this->capabilities = [];
    }

    public function toArray(): array
    {
        return array_map(
            static function (Capability $capability): array {
                return $capability->toArray();
            },
            $this->all()
        );
    }

    public static function fromArray(array $data): self
    {
        $registry = new self();

        foreach ($data as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('CapabilityRegistry entries must be arrays.');
            }

            $registry->register(Capability::fromArray($item));
        }

        return $registry;
    }
}
