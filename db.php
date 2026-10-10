<?php
/**
 * 数据库管理入口（Adminer）快捷跳转
 *
 * 作用：把访问者直接送到 adminer.php，并把服务器、用户名、库名都预先填好，
 *       只需要再输入一次数据库密码。
 *
 * 为什么需要它：
 *   一、Adminer 登录失败时，会把登录表单里填错的值原样写回网址。
 *      一旦地址栏里留下了错的服务器地址（比如把用户名填进了"服务器"那一栏，
 *      地址就变成 ?server=root），之后每次打开都带着这个错地址，怎么改都登不上。
 *      从这个文件重新进一次，地址就会被纠正回来。
 *   二、Adminer 在 800px 以下的屏幕（手机）会把页面底部的操作栏整个隐藏，
 *      而导出入口就在那个底栏里，手机上只剩"删除"按钮。
 *      所以这里支持 ?to=export，直接跳到导出页面。
 *
 * 用法：
 *   db.php            进数据库首页
 *   db.php?to=export  直接进导出页面
 *
 * 本文件不做任何权限限制，Adminer 本身也是官方原版，任何人都可以打开。
 */

$__qbServer   = '127.0.0.1';
$__qbUsername = 'root';
$__qbDatabase = 'typecho20240517';

$__qbTarget = 'adminer.php'
    . '?server=' . rawurlencode($__qbServer)
    . '&username=' . rawurlencode($__qbUsername)
    . '&db=' . rawurlencode($__qbDatabase);

if (isset($_GET['to']) && 'export' === $_GET['to']) {
    $__qbTarget .= '&dump=';
}

header('Location: ' . $__qbTarget, true, 302);
exit;