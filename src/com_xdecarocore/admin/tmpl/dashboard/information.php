<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero"><div><h2><?php echo Text::_('COM_XDECAROCORE_INFORMATION'); ?></h2><p><?php echo Text::_('COM_XDECAROCORE_INFORMATION_DESC'); ?></p></div></div>
    <div class="xdecaro-suite__info-grid">
        <div class="xdecaro-card">
            <div class="xdecaro-card__header"><h3 class="xdecaro-card__title">Core</h3></div>
            <div class="xdecaro-card__body xdecaro-suite__definition-list">
                <div><span><?php echo Text::_('COM_XDECAROCORE_VERSION'); ?></span><strong><?php echo htmlspecialchars($this->coreVersion, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div><span>Joomla</span><strong><?php echo htmlspecialchars($this->joomlaVersion, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div><span>PHP</span><strong><?php echo htmlspecialchars($this->phpVersion, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div><span><?php echo Text::_('COM_XDECAROCORE_KNOWN_PRODUCTS'); ?></span><strong><?php echo (int) $this->snapshot['summary']['known']; ?></strong></div>
            </div>
        </div>
        <div class="xdecaro-card">
            <div class="xdecaro-card__header"><h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_LICENSING'); ?></h3></div>
            <div class="xdecaro-card__body">
                <span class="xdecaro-badge xdecaro-badge--warning"><?php echo Text::_('COM_XDECAROCORE_DEVELOPMENT_MODE'); ?></span>
                <p class="xdecaro-suite__note"><?php echo Text::_('COM_XDECAROCORE_LICENSING_DEFERRED'); ?></p>
            </div>
        </div>
    </div>
</div>
