<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero">
        <div>
            <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_SUITE'); ?></span>
            <h2><?php echo Text::_('COM_XDECAROCORE_GUIDE'); ?></h2>
            <p><?php echo Text::_('COM_XDECAROCORE_GUIDE_DESC'); ?></p>
        </div>
    </div>

    <div class="xdecaro-suite__info-grid">
        <section class="xdecaro-card xdecaro-suite__info-card">
            <div class="xdecaro-card__header">
                <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_GUIDE_NAVIGATION'); ?></span>
                <h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_GUIDE_PAGES'); ?></h3>
            </div>
            <div class="xdecaro-card__body">
                <dl class="xdecaro-suite__definition-list">
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_DASHBOARD'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_DASHBOARD'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_PRODUCTS'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_PRODUCTS'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_EXTENSIONS'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_EXTENSIONS'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_UPDATES'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_UPDATES'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_DIAGNOSTICS'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_DIAGNOSTICS'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_INFORMATION'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_INFORMATION'); ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="xdecaro-card xdecaro-suite__info-card">
            <div class="xdecaro-card__header">
                <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_GUIDE_STATUS'); ?></span>
                <h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_GUIDE_STATUS_TITLE'); ?></h3>
            </div>
            <div class="xdecaro-card__body">
                <dl class="xdecaro-suite__definition-list">
                    <div><dt><span class="xdecaro-badge xdecaro-badge--success"><?php echo Text::_('COM_XDECAROCORE_STATUS_CURRENT'); ?></span></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_STATUS_CURRENT'); ?></dd></div>
                    <div><dt><span class="xdecaro-badge"><?php echo Text::_('COM_XDECAROCORE_STATUS_NOT_INSTALLED'); ?></span></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_STATUS_NOT_INSTALLED'); ?></dd></div>
                    <div><dt><span class="xdecaro-badge xdecaro-badge--warning"><?php echo Text::_('COM_XDECAROCORE_STATUS_DEVELOPMENT'); ?></span></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_STATUS_DEVELOPMENT'); ?></dd></div>
                    <div><dt><span class="xdecaro-badge xdecaro-badge--warning"><?php echo Text::_('COM_XDECAROCORE_STATUS_PLANNED'); ?></span></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_STATUS_PLANNED'); ?></dd></div>
                    <div><dt><span class="xdecaro-badge xdecaro-badge--warning"><?php echo Text::_('COM_XDECAROCORE_STATUS_UPDATE'); ?></span></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_STATUS_UPDATE'); ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="xdecaro-card xdecaro-suite__info-card xdecaro-suite__info-card--full">
            <div class="xdecaro-card__header">
                <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_GUIDE_TOOLBAR'); ?></span>
                <h3 class="xdecaro-card__title"><?php echo Text::_('COM_XDECAROCORE_GUIDE_TOOLBAR_TITLE'); ?></h3>
            </div>
            <div class="xdecaro-card__body">
                <dl class="xdecaro-suite__definition-list">
                    <div><dt><?php echo Text::_('JTOOLBAR_BACK'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_BACK'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROCORE_GUIDE'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_GUIDE'); ?></dd></div>
                    <div><dt><?php echo Text::_('JOPTIONS'); ?></dt><dd><?php echo Text::_('COM_XDECAROCORE_GUIDE_OPTIONS'); ?></dd></div>
                </dl>
                <p class="xdecaro-suite__note"><?php echo Text::_('COM_XDECAROCORE_GUIDE_PACKAGE_CONTENTS'); ?></p>
            </div>
        </section>
    </div>
</div>
