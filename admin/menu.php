<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php
/**
 * 后台顶栏（精简版）
 * 左上角：只有「控制台」一个按钮，点进去是功能总览页（index.php）
 * 右上角：备份 / 账号名 / 登出 / 网站，共四个功能，备份在账号名左边
 * 手机上以前要展开「菜单」才能看到的功能，已经全部搬到控制台页面里
 */
?>
<header class="typecho-head-nav" role="navigation">
    <nav>
        <menu>
            <li class="menu-console">
                <a href="<?php $options->adminUrl(); ?>" title="<?php _e('控制台'); ?>"><?php _e('控制台'); ?></a>
            </li>
            <li class="operate">
                <?php if ($user->pass('administrator', true)): ?>
                    <a class="backup" href="<?php $options->adminUrl('backup.php'); ?>"
                       title="<?php _e('备份'); ?>"><?php _e('备份'); ?></a>
                <?php endif; ?>
                <?php \Typecho\Plugin::factory('admin/menu.php')->call('navBar'); ?><a title="<?php
                if ($user->logged > 0) {
                    $logged = new \Typecho\Date($user->logged);
                    _e('最后登录: %s', $logged->word());
                }
                ?>" href="<?php $options->adminUrl('profile.php'); ?>" class="author"><?php $user->screenName(); ?></a><a
                    class="exit" href="<?php $options->logoutUrl(); ?>"><?php _e('登出'); ?></a><a
                    href="<?php $options->siteUrl(); ?>"><?php _e('网站'); ?></a>
            </li>
        </menu>
    </nav>
</header>
