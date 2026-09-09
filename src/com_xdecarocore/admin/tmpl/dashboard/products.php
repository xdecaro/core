<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$products = $this->snapshot['products'];
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
    if (in_array($status, ['current', 'ahead'], true)) return 'xdecaro-badge--success';
    if (in_array($status, ['update', 'development', 'planned'], true)) return 'xdecaro-badge--warning';
    if ($status === 'partial') return 'xdecaro-badge--danger';
    return '';
};
$channelLabel = static function (string $channel): string {
    $map = [
        'stable' => 'COM_XDECAROCORE_CHANNEL_STABLE',
        'prerelease' => 'COM_XDECAROCORE_CHANNEL_PRERELEASE',
        'development' => 'COM_XDECAROCORE_CHANNEL_DEVELOPMENT',
        'planned' => 'COM_XDECAROCORE_CHANNEL_PLANNED',
    ];
    return Text::_($map[$channel] ?? 'COM_XDECAROCORE_STATUS_UNKNOWN');
};
$channelClass = static function (string $channel): string {
    if ($channel === 'stable') return 'xdecaro-badge--success';
    if (in_array($channel, ['prerelease', 'development', 'planned'], true)) return 'xdecaro-badge--warning';
    return '';
};
$extensionLabel = static function (array $extension): string {
    $name = trim((string) ($extension['name'] ?? ''));
    if ($name !== '') {
        $translated = Text::_($name);
        if ($translated !== $name || !preg_match('/^[A-Z][A-Z0-9_]+$/', $name)) {
            return $translated;
        }
    }

    $element = trim((string) ($extension['element'] ?? ''));
    $fallback = preg_replace('/^(com|pkg|plg|mod|lib)_/i', '', $element);
    $fallback = preg_replace('/^(xdecaro|decaro)/i', '', (string) $fallback);
    $fallback = trim(str_replace(['_', '-', '/'], ' ', (string) $fallback));

    return $fallback !== '' ? ucwords($fallback) : ($element !== '' ? $element : $name);
};
$label = static function (string $key): string {
    return htmlspecialchars(Text::_($key), ENT_QUOTES, 'UTF-8');
};
?>
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero">
        <div>
            <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_SUITE'); ?></span>
            <h2><?php echo Text::_('COM_XDECAROCORE_PRODUCTS'); ?></h2>
            <p><?php echo Text::_('COM_XDECAROCORE_PRODUCTS_DESC'); ?></p>
        </div>
    </div>

    <div class="xdecaro-card">
        <div class="xdecaro-card__body">
            <div class="xdecaro-table-wrap xdecaro-suite__responsive-wrap">
                <table class="xdecaro-table xdecaro-suite__products-table xdecaro-suite__responsive-table">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_XDECAROCORE_PRODUCT'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROCORE_STATUS'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROCORE_CHANNEL'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROCORE_INSTALLED_VERSION'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROCORE_AVAILABLE_VERSION'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROCORE_CONTENTS'); ?></th>
                            <th class="xdecaro-suite__action-heading"><?php echo Text::_('COM_XDECAROCORE_ACTIONS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product) : ?>
                        <?php
                        $detailId = 'xdecaro-package-' . preg_replace('/[^a-z0-9_-]+/i', '-', (string) $product['key']);
                        $children = $product['children'];
                        ?>
                        <tr class="xdecaro-suite__product-row">
                            <td data-label="<?php echo $label('COM_XDECAROCORE_PRODUCT'); ?>">
                                <strong><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="xdecaro-suite__muted"><?php echo $product['package'] !== '' ? htmlspecialchars($product['package'], ENT_QUOTES, 'UTF-8') : '—'; ?></div>
                            </td>
                            <td data-label="<?php echo $label('COM_XDECAROCORE_STATUS'); ?>">
                                <span class="xdecaro-badge <?php echo $statusClass($product['status']); ?>"><?php echo $statusLabel($product['status']); ?></span>
                                <?php if ($product['disabled_count'] > 0) : ?>
                                    <div class="xdecaro-suite__warning"><?php echo Text::sprintf('COM_XDECAROCORE_DISABLED_CHILDREN', (int) $product['disabled_count']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php echo $label('COM_XDECAROCORE_CHANNEL'); ?>"><span class="xdecaro-badge <?php echo $channelClass($product['channel']); ?>"><?php echo $channelLabel($product['channel']); ?></span></td>
                            <td data-label="<?php echo $label('COM_XDECAROCORE_INSTALLED_VERSION'); ?>"><?php echo $product['installed_version'] !== '' ? htmlspecialchars($product['installed_version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td data-label="<?php echo $label('COM_XDECAROCORE_AVAILABLE_VERSION'); ?>"><?php echo $product['available_version'] !== '' ? htmlspecialchars($product['available_version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td data-label="<?php echo $label('COM_XDECAROCORE_CONTENTS'); ?>">
                                <?php if ($children) : ?>
                                    <button
                                        type="button"
                                        class="xdecaro-button xdecaro-suite__filter-button"
                                        data-xdecaro-package-toggle
                                        aria-expanded="false"
                                        aria-controls="<?php echo htmlspecialchars($detailId, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <span><?php echo Text::sprintf('COM_XDECAROCORE_EXTENSION_COUNT', count($children)); ?></span>
                                        <span class="xdecaro-suite__chevron" aria-hidden="true">⌄</span>
                                    </button>
                                <?php else : ?>
                                    <span class="xdecaro-suite__muted"><?php echo Text::_('COM_XDECAROCORE_NO_INSTALLED_CHILDREN'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="xdecaro-suite__action-cell" data-label="<?php echo $label('COM_XDECAROCORE_ACTIONS'); ?>">
                                <?php if ($product['open_url'] !== '') : ?>
                                    <a class="xdecaro-button" href="<?php echo htmlspecialchars($product['open_url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo Text::_('COM_XDECAROCORE_OPEN'); ?></a>
                                <?php else : ?>
                                    <span class="xdecaro-suite__muted" aria-hidden="true">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php if ($children) : ?>
                            <tr id="<?php echo htmlspecialchars($detailId, ENT_QUOTES, 'UTF-8'); ?>" class="xdecaro-suite__expansion-row" aria-hidden="true">
                                <td colspan="7" class="xdecaro-suite__expansion-cell">
                                    <div class="xdecaro-suite__expansion-panel">
                                        <div class="xdecaro-suite__expansion-inner">
                                            <div class="xdecaro-suite__expansion-content">
                                                <div class="xdecaro-suite__child-heading">
                                                    <strong><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo Text::_('COM_XDECAROCORE_INCLUDED_EXTENSIONS'); ?></strong>
                                                    <span class="xdecaro-suite__muted"><?php echo Text::sprintf('COM_XDECAROCORE_EXTENSION_COUNT', count($children)); ?></span>
                                                </div>
                                                <div class="xdecaro-table-wrap xdecaro-suite__responsive-wrap">
                                                    <table class="xdecaro-table xdecaro-suite__child-table xdecaro-suite__responsive-table">
                                                        <thead>
                                                            <tr>
                                                                <th><?php echo Text::_('COM_XDECAROCORE_EXTENSION'); ?></th>
                                                                <th><?php echo Text::_('COM_XDECAROCORE_TYPE'); ?></th>
                                                                <th><?php echo Text::_('COM_XDECAROCORE_ELEMENT'); ?></th>
                                                                <th><?php echo Text::_('COM_XDECAROCORE_FOLDER_GROUP'); ?></th>
                                                                <th><?php echo Text::_('COM_XDECAROCORE_VERSION'); ?></th>
                                                                <th><?php echo Text::_('COM_XDECAROCORE_STATE'); ?></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php foreach ($children as $child) : ?>
                                                            <?php $switchable = in_array($child['type'], ['plugin', 'module'], true); ?>
                                                            <tr>
                                                                <td data-label="<?php echo $label('COM_XDECAROCORE_EXTENSION'); ?>"><strong><?php echo htmlspecialchars($extensionLabel($child), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                                                <td data-label="<?php echo $label('COM_XDECAROCORE_TYPE'); ?>"><?php echo htmlspecialchars($child['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td data-label="<?php echo $label('COM_XDECAROCORE_ELEMENT'); ?>"><code><?php echo htmlspecialchars($child['element'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                                                <td data-label="<?php echo $label('COM_XDECAROCORE_FOLDER_GROUP'); ?>"><?php echo $child['folder'] !== '' ? htmlspecialchars($child['folder'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                                                <td data-label="<?php echo $label('COM_XDECAROCORE_VERSION'); ?>"><?php echo $child['version'] !== '' ? htmlspecialchars($child['version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                                                <td data-label="<?php echo $label('COM_XDECAROCORE_STATE'); ?>">
                                                                    <span class="xdecaro-badge <?php echo (!$switchable || (int) $child['enabled'] === 1) ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>">
                                                                        <?php echo (!$switchable || (int) $child['enabled'] === 1) ? Text::_('JENABLED') : Text::_('JDISABLED'); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
