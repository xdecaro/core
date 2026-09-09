<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$extensions = $this->snapshot['extensions'];
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
            <h2><?php echo Text::_('COM_XDECAROCORE_EXTENSIONS'); ?></h2>
            <p><?php echo Text::_('COM_XDECAROCORE_EXTENSIONS_DESC'); ?></p>
        </div>
        <span class="xdecaro-badge xdecaro-suite__count-badge"><?php echo Text::sprintf('COM_XDECAROCORE_EXTENSIONS_DETECTED', count($extensions)); ?></span>
    </div>
    <div class="xdecaro-card">
        <div class="xdecaro-card__body">
            <?php if (!$extensions) : ?>
                <div class="xdecaro-empty"><?php echo Text::_('COM_XDECAROCORE_NO_EXTENSIONS'); ?></div>
            <?php else : ?>
                <div class="xdecaro-table-wrap xdecaro-suite__responsive-wrap">
                    <table class="xdecaro-table xdecaro-suite__responsive-table xdecaro-suite__extensions-table">
                        <thead><tr><th><?php echo Text::_('COM_XDECAROCORE_EXTENSION'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_TYPE'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_ELEMENT'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_VERSION'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_STATE'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_PACKAGE_ID'); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($extensions as $extension) : ?>
                            <?php $switchable = in_array($extension['type'], ['plugin', 'module'], true); ?>
                            <tr>
                                <td class="xdecaro-suite__extension-primary" data-label="<?php echo $label('COM_XDECAROCORE_EXTENSION'); ?>">
                                    <strong><?php echo htmlspecialchars($extensionLabel($extension), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="xdecaro-suite__extension-element-mobile xdecaro-suite__muted"><code><?php echo htmlspecialchars($extension['element'], ENT_QUOTES, 'UTF-8'); ?></code></div>
                                    <?php if ($extension['folder'] !== '') : ?><div class="xdecaro-suite__muted"><?php echo htmlspecialchars($extension['folder'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                </td>
                                <td data-label="<?php echo $label('COM_XDECAROCORE_TYPE'); ?>"><?php echo htmlspecialchars($extension['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="xdecaro-suite__extension-element-cell" data-label="<?php echo $label('COM_XDECAROCORE_ELEMENT'); ?>"><code><?php echo htmlspecialchars($extension['element'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td data-label="<?php echo $label('COM_XDECAROCORE_VERSION'); ?>"><?php echo $extension['version'] !== '' ? htmlspecialchars($extension['version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                <td data-label="<?php echo $label('COM_XDECAROCORE_STATE'); ?>"><span class="xdecaro-badge <?php echo (!$switchable || (int) $extension['enabled'] === 1) ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>"><?php echo (!$switchable || (int) $extension['enabled'] === 1) ? Text::_('JENABLED') : Text::_('JDISABLED'); ?></span></td>
                                <td data-label="<?php echo $label('COM_XDECAROCORE_PACKAGE_ID'); ?>"><?php echo (int) $extension['package_id'] > 0 ? (int) $extension['package_id'] : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
