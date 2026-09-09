<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$summary = $this->snapshot['summary'];
$products = $this->snapshot['products'];
$extensions = $this->snapshot['extensions'];
$diagnostics = $this->snapshot['diagnostics'];
$coreProduct = null;
$corePluginEnabled = false;

foreach ($products as $product) {
    if ($product['key'] === 'core') {
        $coreProduct = $product;
        break;
    }
}

foreach ($extensions as $extension) {
    if ($extension['type'] === 'plugin' && $extension['element'] === 'xdecarocore' && $extension['folder'] === 'system') {
        $corePluginEnabled = (int) $extension['enabled'] === 1;
        break;
    }
}

$coreChecks = array_slice($diagnostics, 0, 4);
$systemOk = true;
foreach ($coreChecks as $check) {
    if ($check['level'] === 'danger') {
        $systemOk = false;
        break;
    }
}
$installationCoherent = $coreProduct !== null && $coreProduct['installed'] && !$coreProduct['partial'];
?>
<div class="xdecaro-scope xdecaro-suite">
    <header class="xdecaro-suite__page-header">
        <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_SUITE'); ?></span>
        <h2><?php echo Text::_('COM_XDECAROCORE_INFORMATION'); ?></h2>
        <p><?php echo Text::_('COM_XDECAROCORE_INFORMATION_DESC'); ?></p>
    </header>

    <div class="xdecaro-suite__summary-bar" aria-label="<?php echo Text::_('COM_XDECAROCORE_SUMMARY'); ?>">
        <strong>Core <?php echo htmlspecialchars($this->coreVersion, ENT_QUOTES, 'UTF-8'); ?></strong>
        <span class="xdecaro-badge <?php echo $coreProduct !== null && $coreProduct['status'] === 'current' ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>">
            <?php echo Text::_($coreProduct !== null && $coreProduct['status'] === 'current' ? 'COM_XDECAROCORE_STATUS_CURRENT' : 'COM_XDECAROCORE_STATUS_CHECK'); ?>
        </span>
        <span class="xdecaro-badge <?php echo $systemOk ? 'xdecaro-badge--success' : 'xdecaro-badge--danger'; ?>">
            <?php echo Text::_($systemOk ? 'COM_XDECAROCORE_SYSTEM_OK' : 'COM_XDECAROCORE_SYSTEM_CHECK'); ?>
        </span>
    </div>

    <div class="xdecaro-suite__info-grid">
        <section class="xdecaro-card xdecaro-suite__info-card">
            <div class="xdecaro-card__header xdecaro-suite__card-heading">
                <div>
                    <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_PRODUCT_SECTION'); ?></span>
                    <h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_VERSIONS'); ?></h3>
                </div>
                <span class="xdecaro-badge <?php echo $installationCoherent ? 'xdecaro-badge--success' : 'xdecaro-badge--danger'; ?>">
                    <?php echo Text::_($installationCoherent ? 'COM_XDECAROCORE_COHERENT' : 'COM_XDECAROCORE_CHECK'); ?>
                </span>
            </div>
            <div class="xdecaro-card__body">
                <dl class="xdecaro-suite__definition-list">
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_CORE_VERSION'); ?></dt><dd><span class="xdecaro-badge xdecaro-badge--success"><?php echo htmlspecialchars($this->coreVersion, ENT_QUOTES, 'UTF-8'); ?></span></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_PACKAGE'); ?></dt><dd><code>pkg_xdecarocore</code></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_COMPONENT'); ?></dt><dd><code>com_xdecarocore</code></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_CHANNEL'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_CHANNEL_STABLE'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_DEVELOPER'); ?></dt><dd>Luca De Caro</dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_REPOSITORY'); ?></dt><dd><a href="https://github.com/xdecaro/core" target="_blank" rel="noopener noreferrer">xdecaro/core <span aria-hidden="true">↗</span></a></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_SOFTWARE_LICENSE'); ?></dt><dd>GNU GPL v2 or later</dd></div>
                </dl>
            </div>
        </section>

        <section class="xdecaro-card xdecaro-suite__info-card">
            <div class="xdecaro-card__header xdecaro-suite__card-heading">
                <div>
                    <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_ENVIRONMENT'); ?></span>
                    <h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_SYSTEM'); ?></h3>
                </div>
                <span class="xdecaro-badge <?php echo $systemOk ? 'xdecaro-badge--success' : 'xdecaro-badge--danger'; ?>">
                    <?php echo Text::_($systemOk ? 'COM_XDECAROCORE_SYSTEM_OK' : 'COM_XDECAROCORE_SYSTEM_CHECK'); ?>
                </span>
            </div>
            <div class="xdecaro-card__body">
                <dl class="xdecaro-suite__definition-list">
                    <div><dt>Joomla</dt><dd><?php echo htmlspecialchars($this->joomlaVersion, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>PHP</dt><dd><?php echo htmlspecialchars($this->phpVersion, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_KNOWN_PRODUCTS'); ?></dt><dd><?php echo (int) $summary['known']; ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_INSTALLED'); ?></dt><dd><?php echo (int) $summary['installed']; ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_TECHNICAL_EXTENSIONS'); ?></dt><dd><?php echo (int) $summary['technical_extensions']; ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_CORE_PLUGIN'); ?></dt><dd><span class="xdecaro-badge <?php echo $corePluginEnabled ? 'xdecaro-badge--success' : 'xdecaro-badge--danger'; ?>"><?php echo Text::_($corePluginEnabled ? 'JENABLED' : 'JDISABLED'); ?></span></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_WARNINGS'); ?></dt><dd><?php echo (int) $summary['warnings']; ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="xdecaro-card xdecaro-suite__info-card xdecaro-suite__info-card--full">
            <div class="xdecaro-card__header xdecaro-suite__card-heading">
                <div>
                    <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_DEVELOPMENT'); ?></span>
                    <h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_COMMERCIAL_LICENSING'); ?></h3>
                </div>
                <span class="xdecaro-badge xdecaro-badge--warning"><?php echo Text::_('COM_XDECAROCORE_IN_DEVELOPMENT'); ?></span>
            </div>
            <div class="xdecaro-card__body">
                <p class="xdecaro-suite__note"><?php echo Text::_('COM_XDECAROCORE_LICENSING_DEFERRED'); ?></p>
            </div>
        </section>
    </div>
</div>
