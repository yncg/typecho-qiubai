<?php

if (!file_exists(dirname(__FILE__) . '/config.inc.php')) {
    define('__TYPECHO_ROOT_DIR__', dirname(__FILE__));
    define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');
    define('__TYPECHO_THEME_DIR__', '/usr/themes');
    define('__TYPECHO_ADMIN_DIR__', '/admin/');

    require_once __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';
    \Typecho\Common::init();
} else {
    require_once dirname(__FILE__) . '/config.inc.php';
    $installDb = \Typecho\Db::get();
}

function install_get_lang(): string
{
    return 'zh_CN';
}

function install_get_site_url(): string
{
    return \Typecho\Request::getInstance()->getRequestRoot();
}

function install_get_default_routers(): array
{
    return [
        'index'              => ['url' => '/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'archive'            => ['url' => '/blog/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'do'                 => ['url' => '/action/[action:alpha]', 'widget' => '\Widget\Action', 'action' => 'action'],
        'post'               => ['url' => '/archives/[cid:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'attachment'         => ['url' => '/attachment/[cid:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'category'           => ['url' => '/category/[slug]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'tag'                => ['url' => '/tag/[slug]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'author'             => ['url' => '/author/[uid:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'search'             => ['url' => '/search/[keywords]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'index_page'         => ['url' => '/page/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'archive_page'       => ['url' => '/blog/page/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'category_page'      => ['url' => '/category/[slug]/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'tag_page'           => ['url' => '/tag/[slug]/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'author_page'        => ['url' => '/author/[uid:digital]/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'search_page'        => ['url' => '/search/[keywords]/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'archive_year'       => ['url' => '/[year:digital:4]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'archive_month'      => ['url' => '/[year:digital:4]/[month:digital:2]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'archive_day'        => ['url' => '/[year:digital:4]/[month:digital:2]/[day:digital:2]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'archive_year_page'  => ['url' => '/[year:digital:4]/page/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'archive_month_page' => ['url' => '/[year:digital:4]/[month:digital:2]/page/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'archive_day_page'   => ['url' => '/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/', 'widget' => '\Widget\Archive', 'action' => 'render'],
        'comment_page'       => ['url' => '[permalink:string]/comment-page-[commentPage:digital]', 'widget' => '\Widget\CommentPage', 'action' => 'action'],
        'feed'               => ['url' => '/feed[feed:string:0]', 'widget' => '\Widget\Feed', 'action' => 'render'],
        'feedback'           => ['url' => '[permalink:string]/[type:alpha]', 'widget' => '\Widget\Feedback', 'action' => 'action'],
        'page'               => ['url' => '/[slug].html', 'widget' => '\Widget\Archive', 'action' => 'render'],
    ];
}

function install_get_default_options(): array
{
    static $options;

    if (empty($options)) {
        $options = [
            'theme' => 'default',
            'theme:default' => json_encode([
                'logoUrl' => '',
                'sidebarBlock' => [
                    'ShowRecentPosts',
                    'ShowRecentComments',
                    'ShowCategory',
                    'ShowArchive',
                    'ShowOther'
                ]
            ]),
            'timezone' => '28800',
            'lang' => install_get_lang(),
            'charset' => 'UTF-8',
            'contentType' => 'text/html',
            'gzip' => 0,
            'generator' => 'Typecho ' . \Typecho\Common::VERSION,
            'title' => '秋白',
            'description' => 'Your description here.',
            'keywords' => 'typecho,php,blog',
            'rewrite' => 0,
            'frontPage' => 'recent',
            'frontArchive' => 0,
            'commentsRequireMail' => 1,
            'commentsWhitelist' => 0,
            'commentsRequireUrl' => 0,
            'commentsRequireModeration' => 0,
            'plugins' => json_encode(['activated' => [], 'handles' => []]),
            'commentDateFormat' => 'F jS, Y \a\t h:i a',
            'siteUrl' => install_get_site_url(),
            'defaultCategory' => 1,
            'allowRegister' => 0,
            'defaultAllowComment' => 1,
            'defaultAllowPing' => 1,
            'defaultAllowFeed' => 1,
            'pageSize' => 5,
            'postsListSize' => 10,
            'commentsListSize' => 10,
            'commentsHTMLTagAllowed' => null,
            'postDateFormat' => 'Y-m-d',
            'feedFullText' => 1,
            'editorSize' => 350,
            'autoSave' => 0,
            'markdown' => 1,
            'xmlrpcMarkdown' => 0,
            'commentsMaxNestingLevels' => 5,
            'commentsPostTimeout' => 24 * 3600 * 30,
            'commentsUrlNofollow' => 1,
            'commentsShowUrl' => 1,
            'commentsMarkdown' => 0,
            'commentsPageBreak' => 0,
            'commentsThreaded' => 1,
            'commentsPageSize' => 20,
            'commentsPageDisplay' => 'last',
            'commentsOrder' => 'ASC',
            'commentsCheckReferer' => 1,
            'commentsAutoClose' => 0,
            'commentsPostIntervalEnable' => 1,
            'commentsPostInterval' => 60,
            'commentsShowCommentOnly' => 0,
            'commentsAvatar' => 1,
            'commentsAvatarRating' => 'G',
            'commentsAntiSpam' => 1,
            'routingTable' => json_encode(install_get_default_routers()),
            'actionTable' => json_encode([]),
            'panelTable' => json_encode([]),
            'attachmentTypes' => '@image@',
            'secret' => \Typecho\Common::randString(32, true),
            'installed' => 0,
            'allowXmlRpc' => 0
        ];
    }

    return $options;
}

function install_config_file(string $adapter, string $dbPrefix, array $dbConfig, bool $return = false): string
{
    $code = "<" . "?php
define('__TYPECHO_ROOT_DIR__', dirname(__FILE__));
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');
define('__TYPECHO_THEME_DIR__', '/usr/themes');
define('__TYPECHO_ADMIN_DIR__', '/admin/');

define('__TYPECHO_DYNAMIC_SITE_URL__', true);

require_once __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';
\Typecho\Common::init();

\$db = new \Typecho\Db('{$adapter}', '{$dbPrefix}');
\$db->addServer(" . (var_export($dbConfig, true)) . ", \Typecho\Db::READ | \Typecho\Db::WRITE);
\Typecho\Db::set(\$db);
";

    if (!$return) {
        @file_put_contents(__TYPECHO_ROOT_DIR__ . '/config.inc.php', $code);
    }

    return $code;
}

function install_check(string $type): bool
{
    if ($type == 'config') {
        return file_exists(__TYPECHO_ROOT_DIR__ . '/config.inc.php');
    }

    global $installDb;

    if (empty($installDb)) {
        return false;
    }

    try {
        $installed = $installDb->fetchRow($installDb->select()->from('table.options')
            ->where('user = 0 AND name = ?', 'installed'));

        return !empty($installed['value']);
    } catch (\Throwable $e) {
        return false;
    }
}

function install_db_exists(\mysqli $mysqli, string $dbName): bool
{
    $dbName = $mysqli->real_escape_string($dbName);
    $result = $mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbName}'");

    if (!$result) {
        return false;
    }

    $found = $result->num_rows > 0;
    $result->free();

    return $found;
}

function install_new_db_name(\mysqli $mysqli, string $confirmed): string
{
    if (!empty($confirmed)) {
        if (!preg_match('/^[A-Za-z0-9_]{1,60}$/', $confirmed)) {
            install_raise_error(_t('数据库名不合法'));
        }

        if (install_db_exists($mysqli, $confirmed)) {
            install_raise_error(_t('数据库 %s 已存在', $confirmed));
        }

        return $confirmed;
    }

    $baseName = 'typecho' . date('Ymd');

    if (!install_db_exists($mysqli, $baseName)) {
        return $baseName;
    }

    for ($i = 0; $i < 30; $i++) {
        $candidate = $baseName . str_pad((string)random_int(0, 99), 2, '0', STR_PAD_LEFT);

        if (!install_db_exists($mysqli, $candidate)) {
            install_throw_json([
                'success' => 0,
                'message' => _t('数据库 %s 已存在, 是否创建 %s ?', $baseName, $candidate),
                'dbName'  => $candidate,
                'config'  => null
            ]);
        }
    }

    install_raise_error(_t('无法为数据库找到可用的名称'));
}

function install_read_dump(): string
{
    if (empty($_FILES['dbFile']) || $_FILES['dbFile']['error'] == UPLOAD_ERR_NO_FILE) {
        install_raise_error(_t('请选择数据库文件'));
    }

    $file = $_FILES['dbFile'];

    if ($file['error'] != UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        install_raise_error(_t('数据库文件上传失败'));
    }

    $content = file_get_contents($file['tmp_name']);

    if (empty($content)) {
        install_raise_error(_t('数据库文件内容为空'));
    }

    return $content;
}

function install_parse_dump_name(string $sql): string
{
    if (!preg_match('/CREATE DATABASE\s+`([^`]+)`/i', $sql, $matches)) {
        install_raise_error(_t('数据库文件格式错误'));
    }

    return $matches[1];
}

function install_run_sql(\mysqli $mysqli, string $sql)
{
    if (!$mysqli->multi_query($sql)) {
        install_raise_error(_t('导入数据库失败: "%s"', $mysqli->error));
    }

    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->errno) {
        install_raise_error(_t('导入数据库失败: "%s"', $mysqli->error));
    }
}

function install_get_schema_sql(): string
{
    return <<<SQL
CREATE TABLE `typecho_comments` (
  `coid` int(10) unsigned NOT NULL auto_increment,
  `cid` int(10) unsigned default '0',
  `created` int(10) unsigned default '0',
  `author` varchar(150) default NULL,
  `authorId` int(10) unsigned default '0',
  `ownerId` int(10) unsigned default '0',
  `mail` varchar(150) default NULL,
  `url` varchar(255) default NULL,
  `ip` varchar(64) default NULL,
  `agent` varchar(511) default NULL,
  `text` text,
  `type` varchar(16) default 'comment',
  `status` varchar(16) default 'approved',
  `parent` int(10) unsigned default '0',
  PRIMARY KEY (`coid`),
  KEY `cid` (`cid`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `typecho_contents` (
  `cid` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(150) default NULL,
  `slug` varchar(150) default NULL,
  `created` int(10) unsigned default '0',
  `modified` int(10) unsigned default '0',
  `text` longtext,
  `order` int(10) unsigned default '0',
  `authorId` int(10) unsigned default '0',
  `template` varchar(32) default NULL,
  `type` varchar(16) default 'post',
  `status` varchar(16) default 'publish',
  `password` varchar(32) default NULL,
  `commentsNum` int(10) unsigned default '0',
  `allowComment` char(1) default '0',
  `allowPing` char(1) default '0',
  `allowFeed` char(1) default '0',
  `parent` int(10) unsigned default '0',
  PRIMARY KEY (`cid`),
  UNIQUE KEY `slug` (`slug`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `typecho_fields` (
  `cid` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(8) default 'str',
  `str_value` text,
  `int_value` int(10) default '0',
  `float_value` float default '0',
  PRIMARY KEY (`cid`,`name`),
  KEY `int_value` (`int_value`),
  KEY `float_value` (`float_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `typecho_metas` (
  `mid` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(150) default NULL,
  `slug` varchar(150) default NULL,
  `type` varchar(32) NOT NULL,
  `description` varchar(150) default NULL,
  `count` int(10) unsigned default '0',
  `order` int(10) unsigned default '0',
  `parent` int(10) unsigned default '0',
  PRIMARY KEY (`mid`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `typecho_options` (
  `name` varchar(32) NOT NULL,
  `user` int(10) unsigned NOT NULL default '0',
  `value` text,
  PRIMARY KEY (`name`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `typecho_relationships` (
  `cid` int(10) unsigned NOT NULL,
  `mid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`cid`,`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `typecho_users` (
  `uid` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) default NULL,
  `password` varchar(64) default NULL,
  `mail` varchar(150) default NULL,
  `url` varchar(150) default NULL,
  `screenName` varchar(32) default NULL,
  `created` int(10) unsigned default '0',
  `activated` int(10) unsigned default '0',
  `logged` int(10) unsigned default '0',
  `group` varchar(16) default 'visitor',
  `authCode` varchar(64) default NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
}

function install_create_site(\Typecho\Db $db)
{
    $hasher = new \Utils\PasswordHash(8, true);
    $time = \Typecho\Date::time();

    $db->query($db->insert('table.users')->rows([
        'name'       => 'admin',
        'password'   => $hasher->hashPassword('123456l'),
        'mail'       => 'admin@localhost',
        'url'        => '',
        'screenName' => 'admin',
        'group'      => 'administrator',
        'created'    => $time
    ]));

    $db->query($db->insert('table.metas')->rows([
        'name'        => _t('默认分类'),
        'slug'        => 'default',
        'type'        => 'category',
        'description' => _t('只是一个默认分类'),
        'count'       => 1
    ]));

    $db->query($db->insert('table.relationships')->rows(['cid' => 1, 'mid' => 1]));

    $db->query($db->insert('table.contents')->rows([
        'title'        => _t('欢迎使用 Typecho'),
        'slug'         => 'start',
        'created'      => $time,
        'modified'     => $time,
        'text'         => '<!--markdown-->' . _t('如果您看到这篇文章,表示您的 blog 已经安装成功.'),
        'authorId'     => 1,
        'type'         => 'post',
        'status'       => 'publish',
        'commentsNum'  => 1,
        'allowComment' => 1,
        'allowPing'    => 1,
        'allowFeed'    => 1,
        'parent'       => 0
    ]));

    $db->query($db->insert('table.contents')->rows([
        'title'        => _t('关于'),
        'slug'         => 'start-page',
        'created'      => $time,
        'modified'     => $time,
        'text'         => '<!--markdown-->' . _t('本页面由 Typecho 创建, 这只是个测试页面.'),
        'authorId'     => 1,
        'order'        => 0,
        'type'         => 'page',
        'status'       => 'publish',
        'commentsNum'  => 0,
        'allowComment' => 1,
        'allowPing'    => 1,
        'allowFeed'    => 1,
        'parent'       => 0
    ]));

    $db->query($db->insert('table.comments')->rows([
        'cid'     => 1,
        'created' => $time,
        'author'  => 'Typecho',
        'ownerId' => 1,
        'url'     => '',
        'ip'      => '127.0.0.1',
        'agent'   => '',
        'text'    => _t('欢迎加入 Typecho 大家族'),
        'type'    => 'comment',
        'status'  => 'approved',
        'parent'  => 0
    ]));

    $options = install_get_default_options();
    $options['installed'] = 1;
    $options['siteUrl'] = install_get_site_url();

    foreach ($options as $key => $value) {
        $db->query($db->insert('table.options')->rows([
            'name'  => $key,
            'user'  => 0,
            'value' => $value
        ]));
    }
}

function install_raise_error($error, $config = null)
{
    install_throw_json([
        'success' => 0,
        'message' => is_string($error) ? nl2br($error) : $error,
        'config'  => $config
    ]);
}

function install_success($step, ?array $config = null)
{
    install_throw_json([
        'success' => 1,
        'message' => $step,
        'config'  => $config
    ]);
}

function install_throw_json($data)
{
    \Typecho\Response::getInstance()->setContentType('application/json')
        ->addResponder(function () use ($data) {
            echo json_encode($data);
        })
        ->respond();
}

function install_redirect(string $url)
{
    \Typecho\Response::getInstance()->setStatus(302)
        ->setHeader('Location', $url)
        ->respond();
}

function install_js_support()
{
    ?>
    <div id="success" class="row typecho-page-main hidden">
        <div class="col-mb-12 col-tb-8 col-tb-offset-2">
            <div class="typecho-page-title">
                <h2><?php _e('安装成功'); ?></h2>
            </div>
            <div id="typecho-welcome">
                <p class="fresh-word">
                    <?php _e('您的用户名是'); ?>: <strong class="warning" id="success-user"></strong><br>
                    <?php _e('您的密码是'); ?>: <strong class="warning" id="success-password"></strong>
                </p>
                <ul>
                    <li><a id="login-url" href=""><?php _e('点击这里访问您的控制面板'); ?></a></li>
                    <li><a id="site-url" href=""><?php _e('点击这里查看您的 Blog'); ?></a></li>
                </ul>
                <p><?php _e('希望您能尽情享用 Typecho 带来的乐趣!'); ?></p>
            </div>
        </div>
    </div>
    <script>
        let form = $('form'), errorBox = $('<div></div>');

        errorBox.addClass('message error')
            .prependTo(form);

        function showError(error) {
            if (typeof error == 'string') {
                $(window).scrollTop(0);

                errorBox
                    .html(error)
                    .addClass('fade');
            } else {
                for (let k in error) {
                    let input = $('#' + k), msg = error[k], p = $('<p></p>');

                    p.addClass('message error')
                        .html(msg)
                        .insertAfter(input);

                    input.on('input', function () {
                        p.remove();
                    });
                }
            }

            return errorBox;
        }

        function showConfirm(message, dbName) {
            $(window).scrollTop(0);
            errorBox.html(message).addClass('fade');

            let button = $('<button type="button"></button>')
                .addClass('btn primary')
                .text('<?php _e('继续创建'); ?>')
                .on('click', function () {
                    submitForm(dbName);
                });

            $('<p></p>').append(button).appendTo(errorBox);
        }

        function toggleDbFile() {
            let isImport = $('#dbMode-import').is(':checked');

            $('#dbFileItem').toggle(isImport);
            $('#dbFile').prop('disabled', !isImport);
        }

        function submitForm(dbName) {
            errorBox.removeClass('fade');
            $('button', form).attr('disabled', 'disabled');
            $('.typecho-option .error', form).remove();

            let payload = new FormData(form[0]);

            if (dbName) {
                payload.set('dbName', dbName);
            }

            $.ajax({
                url: form.attr('action'),
                processData: false,
                contentType: false,
                type: 'POST',
                data: payload,
                success: function (data) {
                    $('button', form).removeAttr('disabled');

                    if (data.success) {
                        if (data.message) {
                            location.href = '?step=' + data.message;
                        } else {
                            let success = $('#success').removeClass('hidden');

                            form.addClass('hidden');

                            if (data.config) {
                                success.addClass('fresh');

                                $('.typecho-page-main:first').addClass('hidden');
                                $('#success-user').html(data.config[0]);
                                $('#success-password').html(data.config[1]);

                                $('#login-url').attr('href', data.config[2]);
                                $('#site-url').attr('href', data.config[3]);
                            }
                        }
                    } else if (data.dbName) {
                        showConfirm(data.message, data.dbName);
                    } else {
                        showError(data.message);
                    }
                },
                error: function (xhr, error) {
                    showError(error)
                }
            });
        }

        $('input[name=dbMode]').on('change', toggleDbFile);
        toggleDbFile();

        form.submit(function (e) {
            e.preventDefault();
            submitForm('');
        });
    </script>
    <?php
}

function install_step_2()
{
    ?>
    <div class="row typecho-page-main">
        <div class="col-mb-12 col-tb-8 col-tb-offset-2">
            <div class="typecho-page-title">
                <h2><?php _e('初始化配置'); ?></h2>
            </div>
            <form autocomplete="off" action="install.php" method="post" enctype="multipart/form-data">
                <p style="margin:0 0 1.4em;color:#999;font-size:.93em;">
                    <?php _e('管理员账号 %s &nbsp;·&nbsp; 密码 %s &nbsp;·&nbsp; 站点标题 %s &nbsp;·&nbsp; 数据表前缀 %s', 'admin', '123456l', '秋白', 'typecho_'); ?>
                </p>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="dbHost"><?php _e('数据库地址'); ?></label>
                        <input type="text" class="text" name="dbHost" id="dbHost" value="127.0.0.1" />
                    </li>
                </ul>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="dbPort"><?php _e('数据库端口'); ?></label>
                        <input type="text" class="text" name="dbPort" id="dbPort" value="3306" />
                    </li>
                </ul>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="dbUser"><?php _e('数据库用户名'); ?></label>
                        <input type="text" class="text" name="dbUser" id="dbUser" value="root" />
                    </li>
                </ul>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="dbPassword"><?php _e('数据库密码'); ?></label>
                        <input type="password" autocomplete="new-password" class="text" name="dbPassword" id="dbPassword" value="" />
                    </li>
                </ul>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label"><?php _e('数据库来源'); ?></label>
                        <span>
                            <input type="radio" name="dbMode" id="dbMode-import" value="import" checked="checked" />
                            <label for="dbMode-import"><?php _e('从文件导入'); ?></label>
                        </span>
                        <span>
                            <input type="radio" name="dbMode" id="dbMode-new" value="new" />
                            <label for="dbMode-new"><?php _e('新建数据库'); ?></label>
                        </span>
                    </li>
                </ul>
                <ul class="typecho-option" id="dbFileItem">
                    <li>
                        <label class="typecho-label" for="dbFile"><?php _e('数据库文件'); ?></label>
                        <input type="file" name="dbFile" id="dbFile" />
                    </li>
                </ul>
                <ul class="typecho-option typecho-option-submit">
                    <li>
                        <button type="submit" class="btn primary"><?php _e('确认, 开始安装 &raquo;'); ?></button>
                    </li>
                </ul>
            </form>
        </div>
    </div>
    <?php
    install_js_support();
}

function install_step_2_perform()
{
    $request = \Typecho\Request::getInstance();
    $config = $request->from(['dbHost', 'dbPort', 'dbUser', 'dbPassword', 'dbMode', 'dbName']);

    $error = (new \Typecho\Validate())
        ->addRule('dbHost', 'required', _t('请填写数据库地址'))
        ->addRule('dbPort', 'required', _t('请填写数据库端口'))
        ->addRule('dbPort', 'isInteger', _t('请填写数据库端口'))
        ->addRule('dbUser', 'required', _t('请填写数据库用户名'))
        ->run($config);

    if (!empty($error)) {
        install_raise_error($error);
    }

    if (!extension_loaded('mysqli')) {
        install_raise_error(_t('缺少 mysqli 扩展'));
    }

    $adapter = 'Pdo_Mysql';
    $dbPrefix = 'typecho_';
    $dbHost = $config['dbHost'];
    $dbPort = intval($config['dbPort']);
    $dbUser = $config['dbUser'];
    $dbPassword = $config['dbPassword'];
    $dbCharset = 'utf8mb4';
    $dbEngine = 'InnoDB';

    $dbMode = ($config['dbMode'] ?? '') === 'new' ? 'new' : 'import';

    set_time_limit(0);
    mysqli_report(MYSQLI_REPORT_OFF);

    $mysqli = @new \mysqli($dbHost, $dbUser, $dbPassword, null, $dbPort);

    if ($mysqli->connect_errno) {
        install_raise_error(_t('对不起, 无法连接数据库, 请先检查数据库配置再继续进行安装: "%s"', $mysqli->connect_error));
    }

    if ($dbMode === 'new') {
        $dbName = install_new_db_name($mysqli, $config['dbName'] ?? '');

        $mysqli->query('CREATE DATABASE `' . $dbName . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $mysqli->select_db($dbName);
        install_run_sql($mysqli, install_get_schema_sql());
    } else {
        $sql = install_read_dump();
        $dbName = install_parse_dump_name($sql);

        $mysqli->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
        install_run_sql($mysqli, $sql);
    }

    $mysqli->close();

    $dbConfig = [
        'host'     => $dbHost,
        'port'     => $dbPort,
        'user'     => $dbUser,
        'password' => $dbPassword,
        'charset'  => $dbCharset,
        'database' => $dbName,
        'engine'   => $dbEngine,
    ];

    install_config_file($adapter, $dbPrefix, $dbConfig);

    if (!install_check('config')) {
        install_raise_error(
            _t('安装程序无法自动创建 <strong>config.inc.php</strong> 文件'),
            ['code' => install_config_file($adapter, $dbPrefix, $dbConfig, true)]
        );
    }

    try {
        $db = new \Typecho\Db($adapter, $dbPrefix);
        $db->addServer($dbConfig, \Typecho\Db::READ | \Typecho\Db::WRITE);
        \Typecho\Db::set($db);
        $db->query('SELECT 1=1');

        if ($dbMode === 'new') {
            install_create_site($db);
        } else {
            $hasher = new \Utils\PasswordHash(8, true);

            $db->query($db->update('table.users')
                ->rows(['password' => $hasher->hashPassword('123456l')])
                ->where('name = ?', 'admin'));

            $db->query($db->update('table.options')
                ->rows(['value' => '秋白'])->where('name = ?', 'title'));

            $db->query($db->update('table.options')
                ->rows(['value' => 'Typecho ' . \Typecho\Common::VERSION])->where('name = ?', 'generator'));

            $db->query($db->update('table.options')
                ->rows(['value' => 0])->where('name = ?', 'allowXmlRpc'));
        }
    } catch (\Throwable $e) {
        install_raise_error(_t('安装程序捕捉到以下错误: "%s". 程序被终止, 请检查您的配置信息.', $e->getMessage()));
    }

    if (!defined('__TYPECHO_DYNAMIC_SITE_URL__')) {
        define('__TYPECHO_DYNAMIC_SITE_URL__', true);
    }

    $options = \Widget\Options::alloc();
    $parts = parse_url($options->loginAction);
    $parts['query'] = http_build_query([
        'name'     => 'admin',
        'password' => '123456l',
        'referer'  => $options->adminUrl
    ]);
    $loginUrl = \Typecho\Common::buildUrl($parts);

    install_success(0, [
        'admin',
        '123456l',
        \Widget\Security::alloc()->getTokenUrl($loginUrl, $request->getReferer()),
        $options->siteUrl
    ]);
}

function install_dispatch()
{
    $options = \Widget\Options::alloc(install_get_default_options());
    \Widget\Init::alloc();

    if (install_check('config') && install_check('db_data')) {
        install_redirect($options->siteUrl);
        exit(1);
    }

    if (\Typecho\Request::getInstance()->isPost()) {
        install_step_2_perform();
        exit;
    }
    ?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="<?php _e('UTF-8'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php _e('秋白'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php $options->adminStaticUrl('css', 'normalize.css') ?>" />
    <link rel="stylesheet" type="text/css" href="<?php $options->adminStaticUrl('css', 'grid.css') ?>" />
    <link rel="stylesheet" type="text/css" href="<?php $options->adminStaticUrl('css', 'style.css') ?>" />
    <link rel="stylesheet" type="text/css" href="<?php $options->adminStaticUrl('css', 'install.css') ?>" />
    <script src="<?php $options->adminStaticUrl('js', 'jquery.js'); ?>"></script>
</head>
<body>
    <div class="body container">
        <h1><span class="i-logo"><?php _e('秋白'); ?></span></h1>
        <?php install_step_2(); ?>
    </div>
</body>
</html>
    <?php
}

install_dispatch();
