<?php
if (!defined('__TYPECHO_ADMIN__')) {
    exit;
}

$header = '<link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'normalize.css', true) . '">
<link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'grid.css', true) . '">
<link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'style.css', true) . '">
<link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'qiubai.css', true) . '">';

/** 站点图标（后台也要显示，否则浏览器标签页上是空白图标）
    只用矢量 SVG，不保留 PNG / ICO
    ?v= 取图标文件修改时间，换图后自动破缓存 */
$__iconVer = @filemtime(__TYPECHO_ROOT_DIR__ . '/apple-touch-icon.svg') ?: time();
$header .= '<link rel="apple-touch-icon" href="'
    . \Typecho\Common::url('apple-touch-icon.svg', $options->rootUrl) . '?v=' . $__iconVer . '">
<link rel="icon" type="image/svg+xml" href="'
    . \Typecho\Common::url('apple-touch-icon.svg', $options->rootUrl) . '?v=' . $__iconVer . '">';

/** 注册一个初始化插件 */
$header = \Typecho\Plugin::factory('admin/header.php')->filter('header', $header);

?><!DOCTYPE HTML>
<html>
    <head>
        <meta charset="<?php $options->charset(); ?>">
        <meta name="renderer" content="webkit">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title><?php _e('%s - %s - Powered by Typecho', $menu->title, $options->title); ?></title>
        <meta name="robots" content="noindex, nofollow">
        <?php echo $header; ?>
    </head>
    <body<?php if (isset($bodyClass)) {echo ' class="' . $bodyClass . '"';} ?>>
