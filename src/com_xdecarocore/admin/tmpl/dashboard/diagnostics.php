<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$checks = $this->snapshot['diagnostics'];
$statusLabel = static function (string $level): string {
    if ($level === 'success') return Text::_('COM_XDECAROCORE_DIAGNOSTIC_OK');
    if ($level === 'warning') return Text::_('COM_XDECAROCORE_DIAGNOSTIC_WARNING');
    return Text::_('COM_XDECAROCORE_DIAGNOSTIC_ERROR');
};
$statusClass = static function (string $level): string {
    if ($level === 'success') return 'xdecaro-badge--success';
    if ($level === 'warning') return 'xdecaro-badge--warning';
    return 'xdecaro-badge--danger';
};
?>
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero">
        <div>
            <h2><?php echo Text::_('COM_XDECAROCORE_DIAGNOSTICS'); ?></h2>
            <p><?php echo Text::_('COM_XDECAROCORE_DIAGNOSTICS_DESC'); ?></p>
        </div>
    </div>

    <div class="xdecaro-card">
        <div class="xdecaro-card__header">
            <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_SYSTEM'); ?></span>
            <h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_DIAGNOSTIC_CHECKS'); ?></h3>
        </div>
        <div class="xdecaro-card__body xdecaro-suite__diagnostic-list">
            <?php foreach ($checks as $check) : ?>
                <div class="xdecaro-suite__diagnostic-row">
                    <div>
                        <strong><?php echo htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <div class="xdecaro-suite__muted"><?php echo htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <span class="xdecaro-badge <?php echo $statusClass($check['level']); ?>"><?php echo $statusLabel($check['level']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
