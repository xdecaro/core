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

final class RelationReference
{
    private $source;
    private $target;
    private $type;

    public function __construct(EntityReference $source, EntityReference $target, string $type)
    {
        $type = trim($type);

        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $type)) {
            throw new InvalidArgumentException('Invalid relation type.');
        }

        $this->source = $source;
        $this->target = $target;
        $this->type   = $type;
    }

    public function getSource(): EntityReference { return $this->source; }
    public function getTarget(): EntityReference { return $this->target; }
    public function getType(): string { return $this->type; }

    public function toArray(): array
    {
        return [
            'source' => $this->source->toArray(),
            'target' => $this->target->toArray(),
            'type'   => $this->type,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['source'], $data['target'], $data['type']) || !is_array($data['source']) || !is_array($data['target'])) {
            throw new InvalidArgumentException('Relation reference requires source, target and type.');
        }

        return new self(
            EntityReference::fromArray($data['source']),
            EntityReference::fromArray($data['target']),
            (string) $data['type']
        );
    }
}
