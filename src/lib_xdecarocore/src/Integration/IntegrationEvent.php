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
use JsonException;

final class IntegrationEvent
{
    private $name;
    private $version;
    private $source;
    private $payload;
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

        if ($occurredAt !== null && $occurredAt !== '' && !$this->isRfc3339Timestamp($occurredAt)) {
            throw new InvalidArgumentException('Invalid integration event timestamp.');
        }

        try {
            json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Integration event payload must be JSON serializable.', 0, $exception);
        }

        $this->name       = $name;
        $this->version    = $version;
        $this->source     = $source;
        $this->payload    = $payload;
        $this->occurredAt = $occurredAt !== '' ? $occurredAt : null;
    }

    public function getName(): string { return $this->name; }
    public function getVersion(): string { return $this->version; }
    public function getSource(): ?EntityReference { return $this->source; }
    public function getPayload(): array { return $this->payload; }
    public function getOccurredAt(): ?string { return $this->occurredAt; }

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

    private function isRfc3339Timestamp(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $value)) {
            return false;
        }

        $format = strpos($value, '.') !== false ? 'Y-m-d\\TH:i:s.uP' : 'Y-m-d\\TH:i:sP';
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = \DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }
}
