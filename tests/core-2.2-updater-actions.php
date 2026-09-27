<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$controllerPath = $root . '/src/com_xdecarocore/admin/src/Controller/UpdateController.php';
$servicePath = $root . '/src/com_xdecarocore/admin/src/Service/UpdaterService.php';
$viewPath = $root . '/src/com_xdecarocore/admin/src/View/Dashboard/HtmlView.php';
$templatePath = $root . '/src/com_xdecarocore/admin/tmpl/dashboard/updates.php';
$itSysPath = $root . '/src/com_xdecarocore/admin/language/it-IT/com_xdecarocore.sys.ini';
$enSysPath = $root . '/src/com_xdecarocore/admin/language/en-GB/com_xdecarocore.sys.ini';
$itPath = $root . '/src/com_xdecarocore/admin/language/it-IT/com_xdecarocore.ini';
$enPath = $root . '/src/com_xdecarocore/admin/language/en-GB/com_xdecarocore.ini';

foreach ([$controllerPath, $servicePath] as $path) {
    if (!is_file($path)) {
        throw new RuntimeException('Missing updater implementation file: ' . basename($path));
    }
}

$controller = (string) file_get_contents($controllerPath);
foreach ([
    "authorise('core.manage', 'com_installer')",
    'checkToken()',
    "bootComponent('com_installer')",
    "createModel('Update', 'Administrator'",
    "createModel('Updatesites', 'Administrator'",
    'public function find(): void',
    'public function update(): void',
    'public function rebuildSites(): void',
    '->rebuild()',
    "getLanguage()->load('com_installer', JPATH_ADMINISTRATOR)",
    'index.php?option=com_xdecarocore&view=dashboard&layout=updates',
] as $fragment) {
    if (!str_contains($controller, $fragment)) {
        throw new RuntimeException('Updater controller contract missing: ' . $fragment);
    }
}

$service = (string) file_get_contents($servicePath);
foreach ([
    '#__updates',
    'update_id',
    '#__update_sites',
    '#__update_sites_extensions',
    'last_check_timestamp',
    'public function getUpdateIds',
    'public function getUpdateSites',
] as $fragment) {
    if (!str_contains($service, $fragment)) {
        throw new RuntimeException('Updater service contract missing: ' . $fragment);
    }
}

$view = (string) file_get_contents($viewPath);
foreach ([
    'public $updateIds = [];',
    'public $updateSites = [];',
    "if (\$layout === 'updates' && \$this->canManageInstaller)",
    'COM_XDECAROCORE_CHECK_UPDATES',
    'COM_XDECAROCORE_REBUILD_UPDATE_SITES',
    'COM_XDECAROCORE_MANAGE_UPDATE_SITES',
    'update.rebuildSites',
    'index.php?option=com_installer&view=updatesites',
] as $fragment) {
    if (!str_contains($view, $fragment)) {
        throw new RuntimeException('Updates toolbar/view contract missing: ' . $fragment);
    }
}

$template = (string) file_get_contents($templatePath);
foreach ([
    'COM_XDECAROCORE_ACTIONS',
    'COM_XDECAROCORE_UPDATE_NOW',
    'COM_XDECAROCORE_VERIFY',
    'COM_XDECAROCORE_UPDATE_SITES',
    'COM_XDECAROCORE_UPDATE_SITE_LAST_CHECK',
    'COM_XDECAROCORE_UPDATES_AVAILABLE_ONE',
    'name="task" value="update.update"',
] as $fragment) {
    if (!str_contains($template, $fragment)) {
        throw new RuntimeException('Updates template contract missing: ' . $fragment);
    }
}

foreach ([
    'name="task" value="update.rebuildSites"',
    'COM_XDECAROCORE_MANAGE_UPDATE_SITES',
] as $fragment) {
    if (str_contains($template, $fragment)) {
        throw new RuntimeException('Update-site actions must live in the Joomla toolbar, not in the section body: ' . $fragment);
    }
}

foreach ([$itSysPath, $enSysPath] as $path) {
    $source = (string) file_get_contents($path);
    if (!str_contains($source, 'COM_XDECAROCORE_MENU="Core"')) {
        throw new RuntimeException('Administrator menu must be named Core in ' . basename(dirname($path)) . '.');
    }
}

foreach ([$itPath, $enPath] as $path) {
    $source = (string) file_get_contents($path);
    foreach ([
        'COM_XDECAROCORE_CHECK_UPDATES=',
        'COM_XDECAROCORE_UPDATE_NOW=',
        'COM_XDECAROCORE_VERIFY=',
        'COM_XDECAROCORE_UPDATE_SITES=',
        'COM_XDECAROCORE_UPDATE_SITE_LAST_CHECK=',
        'COM_XDECAROCORE_REBUILD_UPDATE_SITES=',
        'COM_XDECAROCORE_UPDATE_SITES_REBUILT=',
        'COM_XDECAROCORE_UPDATES_AVAILABLE_ONE=',
    ] as $fragment) {
        if (!str_contains($source, $fragment)) {
            throw new RuntimeException('Missing updater language key ' . $fragment . ' in ' . basename(dirname($path)) . '.');
        }
    }
}

echo "Core 2.2 updater actions contract passed.\n";
