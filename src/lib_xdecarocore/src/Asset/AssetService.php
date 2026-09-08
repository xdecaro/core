<?php
/**
 * @package     Xdecaro.Core
 * @subpackage  Library
 *
 * @copyright   Copyright (C) 2026 Luca De Caro
 * @license     GNU General Public License version 2 or later
 */

namespace Xdecaro\Core\Asset;

defined('_JEXEC') or die;

use Joomla\CMS\WebAsset\WebAssetManager;

/**
 * Registers and enables the shared Core by xdecaro Web Asset Manager assets.
 *
 * Assets are deliberately opt-in: Core never injects CSS globally.
 */
final class AssetService
{
    public const REGISTRY_EXTENSION = 'plg_system_xdecarocore';
    public const STYLE_FOUNDATION = 'xdecaro.core';
    public const STYLE_COMPONENTS = 'xdecaro.components';

    /**
     * Whether the installed Core media registry is available.
     */
    public function isAvailable(): bool
    {
        return defined('JPATH_ROOT')
            && is_file(JPATH_ROOT . '/media/' . self::REGISTRY_EXTENSION . '/joomla.asset.json');
    }

    /**
     * Register Core assets with the supplied Web Asset Manager.
     *
     * Returns false when the package media is unavailable instead of causing
     * an optional Core consumer to fail with an unknown-asset exception.
     */
    public function register(WebAssetManager $webAssets): bool
    {
        if ($this->assetsRegistered($webAssets)) {
            return true;
        }

        if (!$this->isAvailable()) {
            return false;
        }

        $webAssets->getRegistry()->addExtensionRegistryFile(self::REGISTRY_EXTENSION);

        return $this->assetsRegistered($webAssets);
    }

    /**
     * Enable only the design tokens/foundation stylesheet.
     */
    public function useFoundation(WebAssetManager $webAssets): bool
    {
        if (!$this->register($webAssets)) {
            return false;
        }

        $webAssets->useStyle(self::STYLE_FOUNDATION);

        return true;
    }

    /**
     * Enable the shared UI primitives. The WAM dependency automatically loads
     * the foundation stylesheet first.
     */
    public function useComponents(WebAssetManager $webAssets): bool
    {
        if (!$this->register($webAssets)) {
            return false;
        }

        $webAssets->useStyle(self::STYLE_COMPONENTS);

        return true;
    }

    private function assetsRegistered(WebAssetManager $webAssets): bool
    {
        return $webAssets->assetExists('style', self::STYLE_FOUNDATION)
            && $webAssets->assetExists('style', self::STYLE_COMPONENTS);
    }
}
