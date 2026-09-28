<?php
namespace Joomla\CMS\WebAsset {
    class WebAssetRegistry
    {
        private $manager;
        public $loaded = [];
        public function __construct(WebAssetManager $manager) { $this->manager = $manager; }
        public function addExtensionRegistryFile($extension): void
        {
            $this->loaded[] = $extension;
            $this->manager->registerFakeAsset('style', 'xdecaro.core');
            $this->manager->registerFakeAsset('style', 'xdecaro.components');
            $this->manager->registerFakeAsset('style', 'xdecaro.admin');
            $this->manager->registerFakeAsset('style', 'xdecaro.metrics');
            $this->manager->registerFakeAsset('style', 'xdecaro.list');
            $this->manager->registerFakeAsset('style', 'xdecaro.badges');
            $this->manager->registerFakeAsset('script', 'xdecaro.filterbar');
        }
    }

    class WebAssetManager
    {
        private $assets = [];
        private $used = [];
        private $registry;
        public function __construct() { $this->registry = new WebAssetRegistry($this); }
        public function getRegistry(): WebAssetRegistry { return $this->registry; }
        public function assetExists($type, $name): bool { return isset($this->assets[$type][$name]); }
        public function useStyle($name): self { return $this->useAsset('style', $name); }
        public function useScript($name): self { return $this->useAsset('script', $name); }
        private function useAsset($type, $name): self
        {
            if (!$this->assetExists($type, $name)) throw new \RuntimeException('Unknown fake ' . $type . ' ' . $name);
            $this->used[$type][$name] = true;
            return $this;
        }
        public function registerFakeAsset($type, $name): void { $this->assets[$type][$name] = true; }
        public function isUsed($type, $name): bool { return isset($this->used[$type][$name]); }
    }
}

namespace {
    define('_JEXEC', 1);
    $root = sys_get_temp_dir() . '/xdecaro-core-assets-' . getmypid();
    define('JPATH_ROOT', $root);
    $registryDir = $root . '/media/plg_system_xdecarocore';
    if (!mkdir($registryDir, 0777, true) && !is_dir($registryDir)) throw new \RuntimeException('Unable to create temporary Core media directory.');
    file_put_contents($registryDir . '/joomla.asset.json', '{}');

    require_once __DIR__ . '/../src/lib_xdecarocore/src/Asset/AssetService.php';
    $manager = new \Joomla\CMS\WebAsset\WebAssetManager();
    $service = new \xdecaro\Core\Asset\AssetService();

    if (!$service->isAvailable()) throw new \RuntimeException('AssetService should detect the installed media registry.');
    if (!$service->useFoundation($manager)) throw new \RuntimeException('Foundation asset registration failed.');
    if (!$manager->isUsed('style', \xdecaro\Core\Asset\AssetService::STYLE_FOUNDATION)) throw new \RuntimeException('Foundation style was not enabled.');
    if (!$service->useComponents($manager)) throw new \RuntimeException('Components asset registration failed.');
    if (!$manager->isUsed('style', \xdecaro\Core\Asset\AssetService::STYLE_COMPONENTS)) throw new \RuntimeException('Components style was not enabled.');
    if (!$service->useAdminUi($manager)) throw new \RuntimeException('Admin UI asset registration failed.');
    foreach ([
        \xdecaro\Core\Asset\AssetService::STYLE_ADMIN,
        \xdecaro\Core\Asset\AssetService::STYLE_METRICS,
        \xdecaro\Core\Asset\AssetService::STYLE_LIST,
        \xdecaro\Core\Asset\AssetService::STYLE_BADGES,
    ] as $style) {
        if (!$manager->isUsed('style', $style)) throw new \RuntimeException('Expected shared style was not enabled: ' . $style);
    }
    if (!$manager->isUsed('script', \xdecaro\Core\Asset\AssetService::SCRIPT_FILTERBAR)) throw new \RuntimeException('Shared filterbar script was not enabled.');
    if (count($manager->getRegistry()->loaded) !== 1) throw new \RuntimeException('Asset registry must not be loaded more than once per manager.');

    $metricCss = (string) file_get_contents(__DIR__ . '/../src/plg_system_xdecarocore/media/css/metrics.css');
    foreach (['.xdecaro-suite .xdecaro-suite__metric.card', 'padding: 0;', '.xdecaro-suite__metric.card > .card-body', 'padding: 0.875rem 1.125rem;'] as $marker) {
        if (!str_contains($metricCss, $marker)) throw new \RuntimeException('Metric card contract missing: ' . $marker);
    }

    $listCss = (string) file_get_contents(__DIR__ . '/../src/plg_system_xdecarocore/media/css/list.css');
    foreach (['.xdecaro-filterbar--panel', '.xdecaro-filterbar__primary', '.xdecaro-filterbar__advanced', '.xdecaro-suite__responsive-table--striped'] as $marker) {
        if (!str_contains($listCss, $marker)) throw new \RuntimeException('Shared list contract missing: ' . $marker);
    }
    $filterbarJs = (string) file_get_contents(__DIR__ . '/../src/plg_system_xdecarocore/media/js/filterbar.js');
    foreach (['data-xdecaro-filterbar-toggle', 'aria-expanded', 'data-xdecaro-filterbar-close'] as $marker) {
        if (!str_contains($filterbarJs, $marker)) throw new \RuntimeException('Shared filterbar behavior missing: ' . $marker);
    }

    unlink($registryDir . '/joomla.asset.json');
    rmdir($registryDir);
    rmdir(dirname($registryDir));
    rmdir($root);
    echo "Core by xdecaro AssetService smoke tests passed.\n";
}
