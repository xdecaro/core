<?php
/**
 * @package     Xdecaro.Core
 * @subpackage  Integration
 *
 * @copyright   Copyright (C) 2026 Luca De Caro
 * @license     GNU General Public License version 2 or later
 */

namespace Xdecaro\Core\Integration;

defined('_JEXEC') or die;

use InvalidArgumentException;

/**
 * Domain-neutral event envelope for optional cross-product integrations.
 *
 * The source product owns the event name and payload schema. Core only provides
 * a stable envelope and never persists, queues or dispatches these events itself.
 */
final class IntegrationEvent
{
    /** @var string */
    private $name;

    /** @var string */
    private $version;

    /** @var EntityReference|null */
    private $source;

    /** @var array<string,mixed> */
    private $payload;

    /** @var string|null */
    private $occurredAt;

    public function __construct(
        string $name,
        array $payload = [],
        ?EntityReference $source = null,
        string $version = '1',
        ?string $occurredAt = null
    ) {
        $name       = trim($name);
        $version    = trim($version);
        $occurredAt = $occurredAt !== null ? trim($occurredAt) : null;

        if (!preg_match('/^[a-z][a-z0-9_.-]{0,127}$/', $name)) {
            throw new InvalidArgumentException('Invalid integration event name.');
        }

        if ($version === '' || !preg_match('/^[0-9]+(?:\.[0-9]+){0,2}$/', $version)) {
            throw new InvalidArgumentException('Invalid integration event version.');
        }

        if ($occurredAt !== null && $occurredAt !== '' && strtotime($occurredAt) === false) {
            throw new InvalidArgumentException('Invalid integration event timestamp.');
        }

        $this->name       = $name;
        $this->version    = $version;
        $this->source     = $source;
        $this->payload    = $payload;
        $this->occurredAt = $occurredAt !== '' ? $occurredAt : null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSource(): ?EntityReference
    {
        return $this->source;
    }

    /** @return array<string,mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getOccurredAt(): ?string
    {
        return $this->occurredAt;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'version'    => $this->version,
            'source'     => $this->source ? $this->source->toArray() : null,
            'payload'    => $this->payload,
            'occurredAt' => $this->occurredAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['name'])) {
            throw new InvalidArgumentException('Integration event requires a name.');
        }

        $source = null;

        if (isset($data['source'])) {
            if (!is_array($data['source'])) {
                throw new InvalidArgumentException('Integration event source must be an entity reference array.');
            }

            $source = EntityReference::fromArray($data['source']);
        }

        $payload = isset($data['payload']) ? $data['payload'] : [];

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Integration event payload must be an array.');
        }

        return new self(
            (string) $data['name'],
            $payload,
            $source,
            isset($data['version']) ? (string) $data['version'] : '1',
            isset($data['occurredAt']) && $data['occurredAt'] !== null ? (string) $data['occurredAt'] : null
        );
    }
}
