<?php
/**
 * 本地头像服务（离线版）
 *
 * 由 var/Typecho/Common.php 的 gravatarUrl() 生成本地址：
 *     /avatar.php?h=<邮箱md5>?s=<尺寸>&r=<评级>&d=<默认>
 * （因 Typecho 直接把 md5 与参数拼在 prefix 之后，故 s 会跟在 h 里）
 *
 * 依据邮箱 md5 生成确定性的 identicon（纯 SVG，无 GD 依赖、无任何网络请求）。
 * 无需任何服务器重写规则，任何 Web 服务器均可直接访问本文件。
 */

/** 取出 h 参数（形如 <md5>?s=80），再从中解析 md5 与尺寸 */
$h    = isset($_GET['h']) ? (string) $_GET['h'] : '';
$hash = '';
$size = 80;

if (preg_match('/([a-f0-9]{32})/i', $h, $m)) {
    $hash = strtolower($m[1]);
}

if (preg_match('/[?&]s=(\d+)/', $h, $m)) {
    $size = (int) $m[1];
} elseif (isset($_GET['s'])) {
    $size = (int) $_GET['s'];
}

if ($hash === '') {
    // 无邮箱时使用固定图案
    $hash = md5('typecho-local-avatar');
}

if ($size < 16) {
    $size = 16;
}
if ($size > 512) {
    $size = 512;
}

/**
 * HSL 转十六进制颜色
 */
function local_avatar_hsl2hex(float $h, float $s, float $l): string
{
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;

    if ($h < 60) {
        [$r, $g, $b] = [$c, $x, 0];
    } elseif ($h < 120) {
        [$r, $g, $b] = [$x, $c, 0];
    } elseif ($h < 180) {
        [$r, $g, $b] = [0, $c, $x];
    } elseif ($h < 240) {
        [$r, $g, $b] = [0, $x, $c];
    } elseif ($h < 300) {
        [$r, $g, $b] = [$x, 0, $c];
    } else {
        [$r, $g, $b] = [$c, 0, $x];
    }

    return sprintf(
        '#%02x%02x%02x',
        (int) round(($r + $m) * 255),
        (int) round(($g + $m) * 255),
        (int) round(($b + $m) * 255)
    );
}

$hue = hexdec(substr($hash, 0, 2));            // 0 - 255
$fg  = local_avatar_hsl2hex($hue / 255 * 360, 0.55, 0.45);
$bg  = '#f0f0f0';

/** 5x5 左右对称图案：取 md5 前 15 位，每 3 位决定一行 */
$rects = '';
for ($y = 0; $y < 5; $y++) {
    for ($x = 0; $x < 3; $x++) {
        $idx = $y * 3 + $x;
        if ((hexdec($hash[$idx]) % 2) === 1) {
            $rects .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1"/>';
            if ($x !== 2) {
                $rects .= '<rect x="' . (4 - $x) . '" y="' . $y . '" width="1" height="1"/>';
            }
        }
    }
}

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=604800');

echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size
    . '" viewBox="0 0 5 5" shape-rendering="crispEdges">'
    . '<rect width="5" height="5" fill="' . $bg . '"/>'
    . '<g fill="' . $fg . '">' . $rects . '</g>'
    . '</svg>';
