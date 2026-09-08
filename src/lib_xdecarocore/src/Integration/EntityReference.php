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

final class EntityReference
{
    private $component;
    private $entity;
    private $id;

    public function __construct(string $component, string $entity, $id)
    {
        $component = trim($component);
        $entity    = trim($entity);

        if (!preg_match('/^com_[a-z0-9][a-z0-9_]*$/', $component)) {
            throw new InvalidArgumentException('Invalid Joomla component element.');
        }

        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $entity)) {
            throw new InvalidArgumentException('Invalid entity type.');
        }

        if (!is_int($id) && !is_string($id)) {
            throw new InvalidArgumentException('Entity ID must be an integer or string.');
        }

        $normalizedId = trim((string) $id);

        if ($normalizedId === '' || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/', $normalizedId)) {
            throw new InvalidArgumentException('Invalid entity ID.');
        }

        $this->component = $component;
        $this->entity    = $entity;
        $this->id        = $normalizedId;
    }

    public function getComponent(): string { return $this->component; }
    public function getEntity(): string { return $this->entity; }
    public function getId(): string { return $this->id; }

    public function key(): string
    {
        return $this->component . ':' . $this->entity . ':' . $this->id;
    }

    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'entity'    => $this->entity,
            'id'        => $this->id,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['component'], $data['entity'], $data['id'])) {
            throw new InvalidArgumentException('Entity reference requires component, entity and id.');
        }

        return new self((string) $data['component'], (string) $data['entity'], $data['id']);
    }
}
