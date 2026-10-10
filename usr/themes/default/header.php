<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php $this->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('标签 %s 下的文章'),
            'author'   => _t('%s 发布的文章')
        ], '', ' - '); ?><?php $this->options->title(); ?></title>

    <!-- 站点图标（引用站点根目录，地址由 siteUrl 动态生成，迁移后自动跟随）
         ?v= 取图标文件的修改时间，浏览器换新图标后会自动重新拉取，不会被旧缓存卡住 -->
    <?php $__iconVer = @filemtime(__TYPECHO_ROOT_DIR__ . '/favicon.ico') ?: time(); ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?php $this->options->siteUrl('favicon-32x32.png'); ?>?v=<?php echo $__iconVer; ?>">
    <link rel="icon" type="image/x-icon" sizes="any" href="<?php $this->options->siteUrl('favicon.ico'); ?>?v=<?php echo $__iconVer; ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php $this->options->siteUrl('favicon.ico'); ?>?v=<?php echo $__iconVer; ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php $this->options->siteUrl('apple-touch-icon.png'); ?>?v=<?php echo $__iconVer; ?>">

    <!-- 使用url函数转换相关路径 -->
    <link rel="stylesheet" href="<?php $this->options->themeUrl('normalize.css'); ?>?v=20261010">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('grid.css'); ?>?v=20261010">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('style.css'); ?>?v=20261010b">

    <!-- 通过自有函数输出HTML头部信息 -->
    <?php $this->header(); ?>
</head>
<body>

<header id="header" class="clearfix">
    <div class="container">
        <div class="row">
            <div class="site-name col-mb-8 col-9">
                <?php if ($this->options->logoUrl): ?>
                    <a id="logo" href="<?php $this->options->siteUrl(); ?>">
                        <img src="<?php $this->options->logoUrl() ?>" alt="<?php $this->options->title() ?>"/>
                    </a>
                <?php else: ?>
                    <a id="logo" href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title() ?></a>
                    <p class="description"><?php $this->options->description() ?></p>
                <?php endif; ?>
            </div>
            <div class="site-search col-mb-4 col-3">
                <form id="search" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
                    <label for="s" class="sr-only"><?php _e('搜索关键字'); ?></label>
                    <input type="text" id="s" name="s" class="text" placeholder="<?php _e('搜索'); ?>"/>
                    <button type="submit" class="submit"><?php _e('搜索'); ?></button>
                </form>
            </div>
            <div class="col-mb-12">
                <nav id="nav-menu" class="clearfix" role="navigation">
                    <a<?php if ($this->is('index')): ?> class="current"<?php endif; ?>
                        href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a>
                    <a href="<?php $this->options->adminUrl(); ?>" title="<?php _e('管理'); ?>"><?php _e('管理'); ?></a>
                    <a href="<?php $this->options->adminUrl('manage-posts.php'); ?>" title="<?php _e('修改'); ?>"><?php _e('修改'); ?></a>
                    <a href="<?php $this->options->adminUrl('write-post.php'); ?>" title="<?php _e('新增'); ?>"><?php _e('新增'); ?></a>
                    <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
                    <?php while ($pages->next()): ?>
                        <a<?php if ($this->is('page', $pages->slug)): ?> class="current"<?php endif; ?>
                            href="<?php $pages->permalink(); ?>"
                            title="<?php $pages->title(); ?>"><?php $pages->title(); ?></a>
                    <?php endwhile; ?>
                </nav>
            </div>
        </div><!-- end .row -->
    </div>
</header><!-- end #header -->
<div id="body">
    <div class="container">
        <div class="row">

    
    
