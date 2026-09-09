<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$extensions = $this->snapshot['extensions'];
?>
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero">
        <div>
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
                <div class="xdecaro-table-wrap">
                    <table class="xdecaro-table">
                        <thead><tr><th><?php echo Text::_('COM_XDECAROCORE_EXTENSION'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_TYPE'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_ELEMENT'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_VERSION'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_STATE'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_PACKAGE_ID'); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($extensions as $extension) : ?>
                            <?php $switchable = in_array($extension['type'], ['plugin', 'module'], true); ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($extension['name'], ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($extension['folder'] !== '') : ?><div class="xdecaro-suite__muted"><?php echo htmlspecialchars($extension['folder'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?></td>
                                <td><?php echo htmlspecialchars($extension['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?php echo htmlspecialchars($extension['element'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?php echo $extension['version'] !== '' ? htmlspecialchars($extension['version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                <td><span class="xdecaro-badge <?php echo (!$switchable || (int) $extension['enabled'] === 1) ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>"><?php echo (!$switchable || (int) $extension['enabled'] === 1) ? Text::_('JENABLED') : Text::_('JDISABLED'); ?></span></td>
                                <td><?php echo (int) $extension['package_id'] > 0 ? (int) $extension['package_id'] : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
