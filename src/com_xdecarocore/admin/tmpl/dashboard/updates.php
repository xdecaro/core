<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$products = $this->snapshot['products'];
$updateCount = 0;
foreach ($products as $product) {
    if ($product['status'] === 'update') {
        $updateCount++;
    }
}
$label = static function (string $key): string {
    return htmlspecialchars(Text::_($key), ENT_QUOTES, 'UTF-8');
};
?>
<form action="<?php echo Route::_('index.php?option=com_xdecarocore&view=dashboard&layout=updates'); ?>" method="post" name="adminForm" id="adminForm">
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero">
        <div>
            <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_SUITE'); ?></span>
            <h2><?php echo Text::_('COM_XDECAROCORE_UPDATES'); ?></h2>
            <p><?php echo Text::_('COM_XDECAROCORE_UPDATES_DESC'); ?></p>
        </div>
        <span class="xdecaro-badge xdecaro-suite__count-badge <?php echo $updateCount > 0 ? 'xdecaro-badge--warning' : 'xdecaro-badge--success'; ?>">
            <?php echo $updateCount > 0 ? Text::sprintf('COM_XDECAROCORE_UPDATES_AVAILABLE_COUNT', $updateCount) : Text::_('COM_XDECAROCORE_NO_UPDATES_AVAILABLE'); ?>
        </span>
    </div>

    <div class="xdecaro-card">
        <div class="xdecaro-card__body">
            <div class="xdecaro-table-wrap xdecaro-suite__responsive-wrap xdecaro-suite__responsive-wrap--stack">
                <table class="xdecaro-table xdecaro-suite__responsive-table xdecaro-suite__updates-table">
                    <caption class="visually-hidden"><?php echo Text::_('COM_XDECAROCORE_UPDATES'); ?></caption>
                    <thead><tr><th scope="col"><?php echo Text::_('COM_XDECAROCORE_PRODUCT'); ?></th><th scope="col"><?php echo Text::_('COM_XDECAROCORE_INSTALLED_VERSION'); ?></th><th scope="col"><?php echo Text::_('COM_XDECAROCORE_AVAILABLE_VERSION'); ?></th><th scope="col"><?php echo Text::_('COM_XDECAROCORE_STATUS'); ?></th><th scope="col"><?php echo Text::_('COM_XDECAROCORE_ACTIONS'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $product) : ?>
                        <?php if (!$product['installed']) continue; ?>
                        <?php $updateId = (int) ($this->updateIds[$product['package']] ?? 0); ?>
                        <tr>
                            <td data-label="<?php echo $label('COM_XDECAROCORE_PRODUCT'); ?>"><strong><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></strong><div class="xdecaro-suite__muted"><?php echo htmlspecialchars($product['package'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                            <td data-label="<?php echo $label('COM_XDECAROCORE_INSTALLED_VERSION'); ?>"><?php echo $product['installed_version'] !== '' ? htmlspecialchars($product['installed_version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td data-label="<?php echo $label('COM_XDECAROCORE_AVAILABLE_VERSION'); ?>"><?php echo $product['available_version'] !== '' ? htmlspecialchars($product['available_version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td class="xdecaro-suite__update-status-cell" data-label="<?php echo $label('COM_XDECAROCORE_STATUS'); ?>">
                                <?php if ($product['status'] === 'update') : ?><span class="xdecaro-badge xdecaro-badge--warning"><?php echo Text::_('COM_XDECAROCORE_STATUS_UPDATE'); ?></span>
                                <?php elseif ($product['status'] === 'partial') : ?><span class="xdecaro-badge xdecaro-badge--danger"><?php echo Text::_('COM_XDECAROCORE_STATUS_PARTIAL'); ?></span>
                                <?php else : ?><span class="xdecaro-badge xdecaro-badge--success"><?php echo Text::_('COM_XDECAROCORE_STATUS_CURRENT'); ?></span><?php endif; ?>
                            </td>
                            <td class="xdecaro-suite__action-cell" data-label="<?php echo $label('COM_XDECAROCORE_ACTIONS'); ?>">
                                <?php if ($product['status'] === 'update' && $updateId > 0 && $this->canManageInstaller) : ?>
                                    <button
                                        type="submit"
                                        class="xdecaro-button"
                                        formaction="<?php echo Route::_('index.php?option=com_xdecarocore&task=update.update&update_id=' . $updateId); ?>"
                                    ><?php echo Text::_('COM_XDECAROCORE_UPDATE_NOW'); ?></button>
                                <?php elseif ($product['status'] === 'partial') : ?>
                                    <a class="xdecaro-button" href="<?php echo Route::_('index.php?option=com_xdecarocore&view=dashboard&layout=diagnostics'); ?>"><?php echo Text::_('COM_XDECAROCORE_VERIFY'); ?></a>
                                <?php else : ?>
                                    <span aria-hidden="true">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="xdecaro-suite__notice" role="note">
                <div class="xdecaro-suite__notice-icon" aria-hidden="true">i</div>
                <div class="xdecaro-suite__notice-content">
                    <strong><?php echo Text::_('COM_XDECAROCORE_UPDATE_CHECK_TITLE'); ?></strong>
                    <p><?php echo Text::_('COM_XDECAROCORE_UPDATES_NOTE'); ?></p>
                </div>
                <?php if ($this->canManageInstaller) : ?>
                    <a class="xdecaro-button" href="<?php echo Route::_('index.php?option=com_installer&view=update'); ?>"><?php echo Text::_('COM_XDECAROCORE_OPEN_JOOMLA_UPDATES'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="xdecaro-card xdecaro-suite__section" aria-labelledby="xdecaro-update-sites-title">
        <div class="xdecaro-card__header xdecaro-suite__section-header">
            <div>
                <span class="xdecaro-suite__eyebrow"><?php echo Text::_('COM_XDECAROCORE_SYSTEM'); ?></span>
                <h3 id="xdecaro-update-sites-title"><?php echo Text::_('COM_XDECAROCORE_UPDATE_SITES'); ?></h3>
                <p><?php echo Text::_('COM_XDECAROCORE_UPDATE_SITES_DESC'); ?></p>
            </div>
            <?php if ($this->canManageInstaller) : ?>
                <a class="xdecaro-button" href="<?php echo Route::_('index.php?option=com_installer&view=updatesites'); ?>"><?php echo Text::_('COM_XDECAROCORE_MANAGE_UPDATE_SITES'); ?></a>
            <?php endif; ?>
        </div>
        <div class="xdecaro-card__body">
            <?php if ($this->updateSites === []) : ?>
                <p class="xdecaro-suite__muted"><?php echo Text::_('COM_XDECAROCORE_NO_UPDATE_SITES'); ?></p>
            <?php else : ?>
                <div class="xdecaro-table-wrap xdecaro-suite__responsive-wrap xdecaro-suite__responsive-wrap--stack">
                    <table class="xdecaro-table xdecaro-suite__responsive-table xdecaro-suite__update-sites-table">
                        <caption class="visually-hidden"><?php echo Text::_('COM_XDECAROCORE_UPDATE_SITES'); ?></caption>
                        <thead><tr><th scope="col"><?php echo Text::_('COM_XDECAROCORE_PACKAGE'); ?></th><th scope="col"><?php echo Text::_('COM_XDECAROCORE_UPDATE_SITE'); ?></th><th scope="col"><?php echo Text::_('COM_XDECAROCORE_STATUS'); ?></th><th scope="col"><?php echo Text::_('COM_XDECAROCORE_UPDATE_SITE_LAST_CHECK'); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($this->updateSites as $site) : ?>
                            <?php
                            $timestamp = (int) $site['last_check_timestamp'];
                            $lastCheck = $timestamp > 0
                                ? HTMLHelper::_('date', gmdate('Y-m-d H:i:s', $timestamp), 'd/m/Y H:i')
                                : Text::_('COM_XDECAROCORE_NEVER');
                            ?>
                            <tr>
                                <td data-label="<?php echo $label('COM_XDECAROCORE_PACKAGE'); ?>"><strong><?php echo htmlspecialchars((string) $site['package'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td data-label="<?php echo $label('COM_XDECAROCORE_UPDATE_SITE'); ?>">
                                    <strong><?php echo htmlspecialchars((string) $site['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="xdecaro-suite__muted"><?php echo htmlspecialchars((string) $site['location'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td data-label="<?php echo $label('COM_XDECAROCORE_STATUS'); ?>">
                                    <span class="xdecaro-badge <?php echo $site['enabled'] ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>">
                                        <?php echo $site['enabled'] ? Text::_('COM_XDECAROCORE_UPDATE_SITE_ENABLED') : Text::_('COM_XDECAROCORE_UPDATE_SITE_DISABLED'); ?>
                                    </span>
                                </td>
                                <td data-label="<?php echo $label('COM_XDECAROCORE_UPDATE_SITE_LAST_CHECK'); ?>"><?php echo htmlspecialchars($lastCheck, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<input type="hidden" name="task" value="">
<?php echo HTMLHelper::_('form.token'); ?>
</form>
