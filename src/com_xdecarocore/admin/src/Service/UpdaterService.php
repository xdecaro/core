<?php
/**
 * @package     xdecaro.Core
 * @subpackage  com_xdecarocore
 */

namespace xdecaro\Component\Core\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class UpdaterService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    /**
     * Return Joomla update row IDs keyed by package element.
     *
     * @param array<int,array<string,mixed>> $products
     * @return array<string,int>
     */
    public function getUpdateIds(array $products): array
    {
        $packages = $this->packageElements($products);
        if ($packages === []) {
            return [];
        }

        try {
            $query = $this->db->getQuery(true)
                ->select([
                    $this->db->quoteName('update_id'),
                    $this->db->quoteName('element'),
                    $this->db->quoteName('version'),
                ])
                ->from($this->db->quoteName('#__updates'))
                ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('package'))
                ->whereIn($this->db->quoteName('element'), $packages, ParameterType::STRING);

            $rows = $this->db->setQuery($query)->loadObjectList();
        } catch (\Throwable $exception) {
            return [];
        }

        $result = [];
        $versions = [];
        foreach ($rows ?: [] as $row) {
            $element = trim((string) $row->element);
            $version = trim((string) $row->version);
            if ($element === '') {
                continue;
            }

            if (!isset($result[$element]) || $version === '' || !isset($versions[$element]) || version_compare($version, $versions[$element], '>')) {
                $result[$element] = (int) $row->update_id;
                $versions[$element] = $version;
            }
        }

        return $result;
    }

    /**
     * Return Joomla update sites associated with installed suite packages.
     *
     * @param array<int,array<string,mixed>> $products
     * @return array<int,array<string,mixed>>
     */
    public function getUpdateSites(array $products): array
    {
        $packages = $this->packageElements($products);
        if ($packages === []) {
            return [];
        }

        try {
            $query = $this->db->getQuery(true)
                ->select([
                    $this->db->quoteName('s.update_site_id', 'update_site_id'),
                    $this->db->quoteName('s.name', 'site_name'),
                    $this->db->quoteName('s.location', 'location'),
                    $this->db->quoteName('s.enabled', 'enabled'),
                    $this->db->quoteName('s.last_check_timestamp', 'last_check_timestamp'),
                    $this->db->quoteName('e.element', 'package'),
                    $this->db->quoteName('e.name', 'extension_name'),
                ])
                ->from($this->db->quoteName('#__update_sites', 's'))
                ->join('INNER', $this->db->quoteName('#__update_sites_extensions', 'usex') . ' ON ' . $this->db->quoteName('usex.update_site_id') . ' = ' . $this->db->quoteName('s.update_site_id'))
                ->join('INNER', $this->db->quoteName('#__extensions', 'e') . ' ON ' . $this->db->quoteName('e.extension_id') . ' = ' . $this->db->quoteName('usex.extension_id'))
                ->where($this->db->quoteName('e.type') . ' = ' . $this->db->quote('package'))
                ->whereIn($this->db->quoteName('e.element'), $packages, ParameterType::STRING)
                ->order($this->db->quoteName('e.element') . ' ASC, ' . $this->db->quoteName('s.name') . ' ASC');

            $rows = $this->db->setQuery($query)->loadObjectList();
        } catch (\Throwable $exception) {
            return [];
        }

        $result = [];
        foreach ($rows ?: [] as $row) {
            $result[] = [
                'update_site_id' => (int) $row->update_site_id,
                'name' => trim((string) $row->site_name),
                'location' => trim((string) $row->location),
                'enabled' => (int) $row->enabled === 1,
                'last_check_timestamp' => (int) $row->last_check_timestamp,
                'package' => trim((string) $row->package),
                'extension_name' => trim((string) $row->extension_name),
            ];
        }

        return $result;
    }

    /**
     * @param array<int,array<string,mixed>> $products
     * @return array<int,string>
     */
    private function packageElements(array $products): array
    {
        $packages = [];
        foreach ($products as $product) {
            $package = trim((string) ($product['package'] ?? ''));
            if ($package !== '' && !empty($product['installed'])) {
                $packages[$package] = $package;
            }
        }

        return array_values($packages);
    }
}
