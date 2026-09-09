<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$checks = $this->snapshot['diagnostics'];
?>
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero"><div><h2><?php echo Text::_('COM_XDECAROCORE_DIAGNOSTICS'); ?></h2><p><?php echo Text::_('COM_XDECAROCORE_DIAGNOSTICS_DESC'); ?></p></div></div>
    <div class="xdecaro-card">
        <div class="xdecaro-card__body xdecaro-suite__checks">
            <?php foreach ($checks as $check) : ?>
                <div class="xdecaro-suite__check">
                    <span class="xdecaro-badge <?php echo $check['level'] === 'success' ? 'xdecaro-badge--success' : ($check['level'] === 'warning' ? 'xdecaro-badge--warning' : 'xdecaro-badge--danger'); ?>"><?php echo htmlspecialchars(strtoupper($check['level']), ENT_QUOTES, 'UTF-8'); ?></span>
                    <div><strong><?php echo htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8'); ?></strong><div class="xdecaro-suite__muted"><?php echo htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8'); ?></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
