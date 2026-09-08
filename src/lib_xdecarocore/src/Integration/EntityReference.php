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
 * Immutable-style reference to an entity owned by a Joomla component.
 *
 * This class intentionally contains no persistence or authorization logic.
 * Knowing a reference never grants access to the referenced entity.
 */
final class EntityReference
{
    /** @var string */
    private $component;

    /** @var string */
    private $entity;

    /** @var string */
    private $id;

    /**
     * @param string     $component Joomla component element, e.g. com_decaromembership.
     * @param string     $entity    Stable public entity type, e.g. member.
     * @param int|string $id        Stable entity identifier.
     */
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

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Stable technical key suitable for in-memory maps and diagnostics.
     *
     * Do not use this value as an authorization token or filesystem path.
     */
    public function key(): string
    {
        return $this->component . ':' . $this->entity . ':' . $this->id;
    }

    /**
     * @return array{component:string,entity:string,id:string}
     */
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
