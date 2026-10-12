<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
?>
<main class="main">
    <div class="container typecho-dashboard">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 welcome-board" role="main">
                <p><?php _e('目前有 <em>%s</em> 篇文章, 并有 <em>%s</em> 条关于你的评论在 <em>%s</em> 个分类中.',
                        $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?>
                    <br><?php _e('点击下面的链接快速开始:'); ?></p>

                <ul id="start-link">
                    <?php if ($user->pass('contributor', true)): ?>
                        <li><a href="<?php $options->adminUrl('write-post.php'); ?>"><?php _e('撰写新文章'); ?></a></li>
                        <?php if ($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->waitingCommentsNum > 0): ?>
                            <li>
                                <a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('待审核的评论'); ?></a>
                                <span class="balloon"><?php $stat->waitingCommentsNum(); ?></span>
                            </li>
                        <?php elseif ($stat->myWaitingCommentsNum > 0): ?>
                            <li>
                                <a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('待审核评论'); ?></a>
                                <span class="balloon"><?php $stat->myWaitingCommentsNum(); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if ($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->spamCommentsNum > 0): ?>
                            <li>
                                <a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('垃圾评论'); ?></a>
                                <span class="balloon"><?php $stat->spamCommentsNum(); ?></span>
                            </li>
                        <?php elseif ($stat->mySpamCommentsNum > 0): ?>
                            <li>
                                <a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('垃圾评论'); ?></a>
                                <span class="balloon"><?php $stat->mySpamCommentsNum(); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if ($user->pass('administrator', true)): ?>
                            <li><a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('更换外观'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('plugins.php'); ?>"><?php _e('插件管理'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('options-general.php'); ?>"><?php _e('系统设置'); ?></a>
                            </li>
                        <?php endif; ?>

                        <?php /* ===== 以下为新增：原先要点右上角「菜单」才能看到的功能，全部搬到控制台这里 ===== */ ?>

                        <?php if ($user->pass('editor', true)): ?>
                            <li><a href="<?php $options->adminUrl('write-page.php'); ?>"><?php _e('创建页面'); ?></a></li>
                        <?php endif; ?>

                        <li><a href="<?php $options->adminUrl('manage-posts.php'); ?>"><?php _e('管理文章'); ?></a></li>

                        <?php if ($user->pass('editor', true)): ?>
                            <li><a href="<?php $options->adminUrl('manage-pages.php'); ?>"><?php _e('管理独立页面'); ?></a></li>
                        <?php endif; ?>

                        <li><a href="<?php $options->adminUrl('manage-comments.php'); ?>"><?php _e('管理评论'); ?></a></li>

                        <?php if ($user->pass('editor', true)): ?>
                            <li><a href="<?php $options->adminUrl('manage-categories.php'); ?>"><?php _e('管理分类'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('manage-tags.php'); ?>"><?php _e('管理标签'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('manage-medias.php'); ?>"><?php _e('管理文件'); ?></a></li>
                        <?php endif; ?>

                        <?php if ($user->pass('administrator', true)): ?>
                            <li><a href="<?php $options->adminUrl('manage-users.php'); ?>"><?php _e('管理用户'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('options-discussion.php'); ?>"><?php _e('评论设置'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('options-reading.php'); ?>"><?php _e('阅读设置'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('options-permalink.php'); ?>"><?php _e('永久链接设置'); ?></a></li>
                        <?php endif; ?>

                        <li><a href="<?php $options->adminUrl('profile.php'); ?>"><?php _e('个人设置'); ?></a></li>
                    <?php endif; ?>
                    <!--<li><a href="<?php $options->adminUrl('profile.php'); ?>"><?php _e('更新我的资料'); ?></a></li>-->
                </ul>
            </div>

            <div class="col-mb-12 col-tb-4" role="complementary">
                <section class="latest-link">
                    <h3><?php _e('最近发布的文章'); ?></h3>
                    <?php \Widget\Contents\Post\Recent::alloc('pageSize=10')->to($posts); ?>
                    <ul>
                        <?php if ($posts->have()): ?>
                            <?php while ($posts->next()): ?>
                                <li>
                                    <span><?php $posts->date('n.j'); ?></span>
                                    <a href="<?php $posts->permalink(); ?>" class="title"><?php $posts->title(); ?></a>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><em><?php _e('暂时没有文章'); ?></em></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<?php include 'footer.php'; ?>
