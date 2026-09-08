<?php
/**
 * Dependency-free smoke test for the public AssetService contract.
 */

namespace Joomla\CMS\WebAsset {
    class WebAssetRegistry
    {
        private $manager;
        public $loaded = [];

        public function __construct(WebAssetManager $manager)
        {
            $this->manager = $manager;
        }

        public function addExtensionRegistryFile($extension): void
        {
            $this->loaded[] = $extension;
            $this->manager->registerFakeAsset('style', 'xdecaro.core');
            $this->manager->registerFakeAsset('style', 'xdecaro.components');
        }
    }

    class WebAssetManager
    {
        private $assets = [];
        private $used = [];
        private $registry;

        public function __construct()
        {
            $this->registry = new WebAssetRegistry($this);
        }

        public function getRegistry(): WebAssetRegistry
        {
            return $this->registry;
        }

        public function assetExists($type, $name): bool
        {
            return isset($this->assets[$type][$name]);
        }

        public function useStyle($name): self
        {
            if (!$this->assetExists('style', $name)) {
                throw new \RuntimeException('Unknown fake style ' . $name);
            }

            $this->used['style'][$name] = true;

            return $this;
        }

        public function registerFakeAsset($type, $name): void
        {
            $this->assets[$type][$name] = true;
        }

        public function isUsed($type, $name): bool
        {
            return isset($this->used[$type][$name]);
        }
    }
}

namespace {
    define('_JEXEC', 1);

    $root = sys_get_temp_dir() . '/xdecaro-core-assets-' . getmypid();
    define('JPATH_ROOT', $root);

    $registryDir = $root . '/media/plg_system_xdecarocore';
    if (!mkdir($registryDir, 0777, true) && !is_dir($registryDir)) {
        throw new \RuntimeException('Unable to create temporary Core media directory.');
    }
    file_put_contents($registryDir . '/joomla.asset.json', '{}');

    require_once __DIR__ . '/../src/lib_xdecarocore/src/Asset/AssetService.php';

    $manager = new \Joomla\CMS\WebAsset\WebAssetManager();
    $service = new \Xdecaro\Core\Asset\AssetService();

    if (!$service->isAvailable()) {
        throw new \RuntimeException('AssetService should detect the installed media registry.');
    }

    if (!$service->useFoundation($manager)) {
        throw new \RuntimeException('Foundation asset registration failed.');
    }

    if (!$manager->isUsed('style', \Xdecaro\Core\Asset\AssetService::STYLE_FOUNDATION)) {
        throw new \RuntimeException('Foundation style was not enabled.');
    }

    if (!$service->useComponents($manager)) {
        throw new \RuntimeException('Components asset registration failed.');
    }

    if (!$manager->isUsed('style', \Xdecaro\Core\Asset\AssetService::STYLE_COMPONENTS)) {
        throw new \RuntimeException('Components style was not enabled.');
    }

    if (count($manager->getRegistry()->loaded) !== 1) {
        throw new \RuntimeException('Asset registry must not be loaded more than once per manager.');
    }

    unlink($registryDir . '/joomla.asset.json');
    rmdir($registryDir);
    rmdir(dirname($registryDir));
    rmdir($root);

    echo "Core by xdecaro AssetService smoke tests passed.\n";
}
