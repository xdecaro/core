<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$summary = $this->snapshot['summary'];
$products = $this->snapshot['products'];
$diagnostics = $this->snapshot['diagnostics'];

$statusLabel = static function (string $status): string {
    $map = [
        'current' => 'COM_XDECAROCORE_STATUS_CURRENT',
        'update' => 'COM_XDECAROCORE_STATUS_UPDATE',
        'ahead' => 'COM_XDECAROCORE_STATUS_AHEAD',
        'partial' => 'COM_XDECAROCORE_STATUS_PARTIAL',
        'not_installed' => 'COM_XDECAROCORE_STATUS_NOT_INSTALLED',
        'development' => 'COM_XDECAROCORE_STATUS_DEVELOPMENT',
        'planned' => 'COM_XDECAROCORE_STATUS_PLANNED',
    ];
    return Text::_($map[$status] ?? 'COM_XDECAROCORE_STATUS_UNKNOWN');
};
$statusClass = static function (string $status): string {
    if (in_array($status, ['current', 'ahead'], true)) {
        return 'xdecaro-badge--success';
    }
    if (in_array($status, ['update', 'development', 'planned'], true)) {
        return 'xdecaro-badge--warning';
    }
    if ($status === 'partial') {
        return 'xdecaro-badge--danger';
    }
    return '';
};
?>
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero">
        <div>
            <h2>xdecaro</h2>
            <p><?php echo Text::_('COM_XDECAROCORE_DASHBOARD_INTRO'); ?></p>
        </div>
        <span class="xdecaro-badge xdecaro-badge--success">Core <?php echo htmlspecialchars($this->coreVersion, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <div class="xdecaro-suite__metrics" aria-label="<?php echo Text::_('COM_XDECAROCORE_SUMMARY'); ?>">
        <div class="xdecaro-card xdecaro-suite__metric"><strong><?php echo (int) $summary['known']; ?></strong><span><?php echo Text::_('COM_XDECAROCORE_KNOWN_PRODUCTS'); ?></span></div>
        <div class="xdecaro-card xdecaro-suite__metric"><strong><?php echo (int) $summary['installed']; ?></strong><span><?php echo Text::_('COM_XDECAROCORE_INSTALLED'); ?></span></div>
        <div class="xdecaro-card xdecaro-suite__metric"><strong><?php echo (int) $summary['not_installed']; ?></strong><span><?php echo Text::_('COM_XDECAROCORE_NOT_INSTALLED'); ?></span></div>
        <div class="xdecaro-card xdecaro-suite__metric"><strong><?php echo (int) $summary['updates']; ?></strong><span><?php echo Text::_('COM_XDECAROCORE_AVAILABLE_UPDATES'); ?></span></div>
        <div class="xdecaro-card xdecaro-suite__metric"><strong><?php echo (int) $summary['technical_extensions']; ?></strong><span><?php echo Text::_('COM_XDECAROCORE_TECHNICAL_EXTENSIONS'); ?></span></div>
    </div>

    <div class="xdecaro-card xdecaro-suite__section">
        <div class="xdecaro-card__header xdecaro-toolbar">
            <div>
                <h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_PRODUCTS'); ?></h3>
                <p class="xdecaro-card__description"><?php echo Text::_('COM_XDECAROCORE_PRODUCTS_DESC'); ?></p>
            </div>
            <span class="xdecaro-toolbar__spacer"></span>
            <a class="xdecaro-button" href="index.php?option=com_xdecarocore&amp;view=dashboard&amp;layout=products"><?php echo Text::_('COM_XDECAROCORE_VIEW_ALL'); ?></a>
        </div>
        <div class="xdecaro-card__body">
            <div class="xdecaro-table-wrap">
                <table class="xdecaro-table">
                    <thead><tr><th><?php echo Text::_('COM_XDECAROCORE_PRODUCT'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_STATUS'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_INSTALLED_VERSION'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_AVAILABLE_VERSION'); ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $product) : ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><span class="xdecaro-badge <?php echo $statusClass($product['status']); ?>"><?php echo $statusLabel($product['status']); ?></span></td>
                            <td><?php echo $product['installed_version'] !== '' ? htmlspecialchars($product['installed_version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td><?php echo $product['available_version'] !== '' ? htmlspecialchars($product['available_version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td><?php if ($product['open_url'] !== '') : ?><a class="xdecaro-button" href="<?php echo htmlspecialchars($product['open_url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo Text::_('COM_XDECAROCORE_OPEN'); ?></a><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="xdecaro-card xdecaro-suite__section">
        <div class="xdecaro-card__header"><h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_DIAGNOSTICS'); ?></h3></div>
        <div class="xdecaro-card__body xdecaro-suite__checks">
            <?php foreach (array_slice($diagnostics, 0, 8) as $check) : ?>
                <div class="xdecaro-suite__check">
                    <span class="xdecaro-badge <?php echo $check['level'] === 'success' ? 'xdecaro-badge--success' : ($check['level'] === 'warning' ? 'xdecaro-badge--warning' : 'xdecaro-badge--danger'); ?>"><?php echo htmlspecialchars(strtoupper($check['level']), ENT_QUOTES, 'UTF-8'); ?></span>
                    <div><strong><?php echo htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8'); ?></strong><div class="xdecaro-suite__muted"><?php echo htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8'); ?></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
