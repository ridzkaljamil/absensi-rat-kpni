<?php
/**
 * Pager kustom KPNI — preserve GET params (search, filter, dll)
 */
$pager->setSurroundCount(2);
$currentPage = $pager->getCurrentPageNumber();
$pageCount   = $pager->getPageCount();

// Preserve semua GET params kecuali page
$params = $_GET;
unset($params['page']);
$qs = http_build_query($params);

$pageUrl = function(int $p) use ($qs) {
    return '?' . ($qs ? $qs . '&' : '') . 'page=' . $p;
};

$s  = 'display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 10px;border-radius:6px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid;transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1);';
$sN = $s . 'background:transparent;color:#064687;border-color:#e5e7eb;';
$sA = $s . 'background:#0960A8;color:white;border-color:#0960A8;font-weight:700;';
$sD = $s . 'background:transparent;color:#c4c4c4;border-color:#f0f0f0;cursor:not-allowed;';
$ho = 'onmouseover="this.style.background=\'#E8F4FD\';this.style.borderColor=\'#0960A8\';this.style.transform=\'translateY(-2px) scale(1.08)\'" onmouseout="this.style.background=\'transparent\';this.style.borderColor=\'#e5e7eb\';this.style.transform=\'\'"';
?>
<div style="display:flex;flex-wrap:wrap;gap:4px;justify-content:center;align-items:center;padding:4px 0;">
    <?php if ($currentPage > 1): ?>
        <a href="<?= $pageUrl(1) ?>" style="<?= $sN ?>" <?= $ho ?>>«</a>
        <a href="<?= $pageUrl($currentPage - 1) ?>" style="<?= $sN ?>" <?= $ho ?>>‹ Prev</a>
    <?php else: ?>
        <span style="<?= $sD ?>">«</span>
        <span style="<?= $sD ?>">‹ Prev</span>
    <?php endif; ?>

    <?php foreach ($pager->links() as $link): ?>
        <?php
            // Override URI supaya preserve GET params
            preg_match('/page=(\d+)/', $link['uri'], $m);
            $p = $m[1] ?? $currentPage;
        ?>
        <?php if ($link['active']): ?>
            <span style="<?= $sA ?>"><?= $link['title'] ?></span>
        <?php else: ?>
            <a href="<?= $pageUrl((int)$p) ?>" style="<?= $sN ?>" <?= $ho ?>><?= $link['title'] ?></a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($currentPage < $pageCount): ?>
        <a href="<?= $pageUrl($currentPage + 1) ?>" style="<?= $sN ?>" <?= $ho ?>>Next ›</a>
        <a href="<?= $pageUrl($pageCount) ?>" style="<?= $sN ?>" <?= $ho ?>>»</a>
    <?php else: ?>
        <span style="<?= $sD ?>">Next ›</span>
        <span style="<?= $sD ?>">»</span>
    <?php endif; ?>
</div>
