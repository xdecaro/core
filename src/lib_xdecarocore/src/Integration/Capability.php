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

final class Capability
{
    private $component;
    private $name;
    private $version;

    public function __construct(string $component, string $name, string $version = '1')
    {
        $component = trim($component);
        $name      = trim($name);
        $version   = trim($version);

        if (!preg_match('/^com_[a-z0-9][a-z0-9_]*$/', $component)) {
            throw new InvalidArgumentException('Invalid Joomla component element.');
        }

        if (!preg_match('/^[a-z][a-z0-9_.-]{0,127}$/', $name)) {
            throw new InvalidArgumentException('Invalid capability name.');
        }

        if ($version === '' || !preg_match('/^[0-9]+(?:\.[0-9]+){0,2}$/', $version)) {
            throw new InvalidArgumentException('Invalid capability version.');
        }

        $this->component = $component;
        $this->name      = $name;
        $this->version   = $version;
    }

    public function getComponent(): string { return $this->component; }
    public function getName(): string { return $this->name; }
    public function getVersion(): string { return $this->version; }

    public function key(): string
    {
        return $this->component . ':' . $this->name . '@' . $this->version;
    }

    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'name'      => $this->name,
            'version'   => $this->version,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['component'], $data['name'])) {
            throw new InvalidArgumentException('Capability requires component and name.');
        }

        return new self(
            (string) $data['component'],
            (string) $data['name'],
            isset($data['version']) ? (string) $data['version'] : '1'
        );
    }
}
