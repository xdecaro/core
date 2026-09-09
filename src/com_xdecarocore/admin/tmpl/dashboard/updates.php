<?php
/** @var \xdecaro\Component\Core\Administrator\View\Dashboard\HtmlView $this */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$products = $this->snapshot['products'];
$updateCount = 0;
foreach ($products as $product) {
    if ($product['status'] === 'update') {
        $updateCount++;
    }
}
?>
<div class="xdecaro-scope xdecaro-suite">
    <div class="xdecaro-suite__hero">
        <div>
            <h2><?php echo Text::_('COM_XDECAROCORE_UPDATES'); ?></h2>
            <p><?php echo Text::_('COM_XDECAROCORE_UPDATES_DESC'); ?></p>
        </div>
        <span class="xdecaro-badge xdecaro-suite__count-badge <?php echo $updateCount > 0 ? 'xdecaro-badge--warning' : 'xdecaro-badge--success'; ?>">
            <?php echo $updateCount > 0 ? Text::sprintf('COM_XDECAROCORE_UPDATES_AVAILABLE_COUNT', $updateCount) : Text::_('COM_XDECAROCORE_NO_UPDATES_AVAILABLE'); ?>
        </span>
    </div>

    <div class="xdecaro-card">
        <div class="xdecaro-card__body">
            <div class="xdecaro-table-wrap">
                <table class="xdecaro-table">
                    <thead><tr><th><?php echo Text::_('COM_XDECAROCORE_PRODUCT'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_INSTALLED_VERSION'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_AVAILABLE_VERSION'); ?></th><th><?php echo Text::_('COM_XDECAROCORE_STATUS'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $product) : ?>
                        <?php if (!$product['installed']) continue; ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></strong><div class="xdecaro-suite__muted"><?php echo htmlspecialchars($product['package'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                            <td><?php echo $product['installed_version'] !== '' ? htmlspecialchars($product['installed_version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td><?php echo $product['available_version'] !== '' ? htmlspecialchars($product['available_version'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td>
                                <?php if ($product['status'] === 'update') : ?><span class="xdecaro-badge xdecaro-badge--warning"><?php echo Text::_('COM_XDECAROCORE_STATUS_UPDATE'); ?></span>
                                <?php elseif ($product['status'] === 'partial') : ?><span class="xdecaro-badge xdecaro-badge--danger"><?php echo Text::_('COM_XDECAROCORE_STATUS_PARTIAL'); ?></span>
                                <?php else : ?><span class="xdecaro-badge xdecaro-badge--success"><?php echo Text::_('COM_XDECAROCORE_STATUS_CURRENT'); ?></span><?php endif; ?>
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
</div>
