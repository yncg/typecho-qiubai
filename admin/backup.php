<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$actionUrl = $security->getTokenUrl(
    \Typecho\Router::url('do', array('action' => 'backup', 'widget' => 'Backup'),
        \Typecho\Common::url('index.php', $options->rootUrl)) . '?do=export');

$backupFiles = \Widget\Backup::alloc()->listFiles();

/** Adminer 数据库管理入口（已并入本站根目录，仅登录的管理员可进入） */
$__qbDbConf = [];

try {
    $__qbDbConf = \Typecho\Db::get()->getConfig(\Typecho\Db::READ)->toArray();
} catch (\Throwable $__qbDbEx) {
    $__qbDbConf = [];
}

$adminerUrl = \Typecho\Common::url('adminer.php', $options->rootUrl)
    . '?server=' . urlencode($__qbDbConf['host'] ?? '127.0.0.1')
    . '&username=' . urlencode($__qbDbConf['user'] ?? '')
    . '&db=' . urlencode($__qbDbConf['database'] ?? '');
?>

<style>
    /* ---- 备份说明页专用样式 ---- */
    .bk-wrap { margin: 1em 0; }
    .bk-block { background: #FFF; border: 1px solid #E2E2DE; border-radius: 3px; padding: 1.1em 1.4em; margin: 0 0 1.1em; }
    .bk-block h3 { margin: 0 0 .6em; font-size: 1.21429em; color: #333; }
    .bk-block h4 { margin: 1em 0 .4em; font-size: 1em; color: #444; }
    .bk-block p { margin: .5em 0; }
    .bk-block ul { margin: .4em 0 .4em 1.2em; }
    .bk-block li { margin: .28em 0; line-height: 1.65; }
    .bk-block code { background: #F3F3F0; padding: 1px 5px; border-radius: 2px; font-size: .92857em; color: #A64B00; }
    .bk-tag { display: inline-block; padding: 0 6px; border-radius: 2px; font-size: .85714em; line-height: 18px; margin-right: 4px; vertical-align: 1px; }
    .bk-tag-yes { background: #DFF0D8; color: #3C763D; }
    .bk-tag-no  { background: #F2DEDE; color: #A94442; }
    .bk-tag-mid { background: #FCF8E3; color: #8A6D3B; }
    /* 彩色提示条 */
    .bk-alert { border-left: 4px solid; padding: .7em 1em; margin: .7em 0; border-radius: 0 3px 3px 0; line-height: 1.7; }
    .bk-danger  { border-color: #D9534F; background: #FDEDEC; color: #A94442; }
    .bk-warn    { border-color: #E0A106; background: #FFF8E5; color: #8A6100; }
    .bk-info    { border-color: #467B96; background: #EAF3F7; color: #2E5A70; }
    .bk-ok      { border-color: #5CB85C; background: #EDF7ED; color: #3C763D; }
    .bk-alert strong { font-weight: 700; }
    /* 对比表 */
    .bk-table { width: 100%; border-collapse: collapse; margin: .6em 0; font-size: .92857em; }
    .bk-table th, .bk-table td { border: 1px solid #E2E2DE; padding: .55em .7em; text-align: left; vertical-align: top; line-height: 1.6; }
    .bk-table th { background: #EFEFEC; color: #333; font-weight: 700; }
    .bk-table td.bk-c1 { background: #FAFAF8; width: 22%; color: #555; font-weight: 700; }
    .bk-table .c-red { color: #B94A48; }
    .bk-table .c-green { color: #3C763D; }
    .bk-red { color: #B94A48; font-weight: 700; }
    .bk-ora { color: #B26A00; font-weight: 700; }
    .bk-blu { color: #467B96; font-weight: 700; }
    .bk-grn { color: #3C763D; font-weight: 700; }
    .bk-action-bar { background: #FFF; border: 1px solid #E2E2DE; border-radius: 3px; padding: 1.1em 1.4em; margin: 0 0 1.1em; }
    .bk-action-bar h3 { margin: 0 0 .6em; font-size: 1.21429em; }
    .bk-action-bar .btn { margin-top: .4em; }
    .bk-desc { color: #888; font-size: .92857em; line-height: 1.6; }
    /* ---- 两大块入口 ---- */
    .bk-panel { background: #FFF; border: 1px solid #E2E2DE; border-top-width: 4px; border-radius: 3px; padding: 1.2em 1.4em 1.4em; margin: 0 0 1.2em; height: 100%; box-sizing: border-box; }
    .bk-panel-1 { border-top-color: #B9B9B4; }
    .bk-panel-2 { border-top-color: #467B96; }
    .bk-panel .bk-no { display: inline-block; min-width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 50%; font-size: .85714em; color: #FFF; background: #B9B9B4; margin-right: 8px; vertical-align: 2px; }
    .bk-panel-2 .bk-no { background: #467B96; }
    .bk-panel h3 { margin: 0 0 .55em; font-size: 1.21429em; color: #333; }
    .bk-panel .bk-sub { color: #999; font-size: .92857em; margin: 0 0 .9em; line-height: 1.6; }
    .bk-panel .bk-usage { margin: .5em 0 0; padding-top: .8em; border-top: 1px dashed #E2E2DE; }
    .bk-panel .bk-usage dt { color: #666; font-weight: 700; margin-top: .7em; }
    .bk-panel .bk-usage dd { margin: .15em 0 0 0; color: #777; line-height: 1.7; }
    .bk-panel .bk-usage ol { margin: .2em 0 0 1.3em; color: #777; line-height: 1.75; }
    .bk-panel .bk-usage ol li { margin: .2em 0; }
    .bk-panel code { background: #F3F3F0; padding: 1px 5px; border-radius: 2px; font-size: .92857em; color: #A64B00; }
    .bk-goto { display: inline-block; }
    .bk-hint { margin-top: .7em; font-size: .92857em; line-height: 1.65; }
</style>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">

            <!-- ============ 大块一：程序自带备份 ============ -->
            <div class="col-mb-12 col-tb-6">
                <div class="bk-panel bk-panel-1">
                    <h3><span class="bk-no">1</span>程序自带备份（.dat 格式）</h3>
                    <p class="bk-sub">
                        在后台直接点按钮就能导出，也可以在下方直接上传文件恢复。数据量小、操作最简单。
                    </p>

                    <div class="bk-alert bk-danger" style="margin-top:0;">
                        <strong>它只备份 6 张内容表：</strong>文章、评论、分类标签、关联、用户、自定义字段。
                        <br><strong>不备份任何设置</strong>（站点标题、地址、主题配置、固定链接、插件状态、密钥全都不在），
                        <strong>也不备份 Base64 配图</strong>（<code>images</code> 表不在范围内），恢复后配图会丢失。
                    </div>

                    <form action="<?php echo $actionUrl; ?>" method="post">
                        <p><button class="btn primary" type="submit">开始备份 &raquo;</button></p>
                        <input tabindex="1" type="hidden" name="do" value="export">
                    </form>

                    <div class="bk-usage">
                        <dl>
                            <dt>恢复数据（只认本程序导出的 .dat 文件）</dt>
                            <dd>恢复是覆盖式的，会先清空上面那 6 张表再写入，请先确认已做好完整备份。</dd>
                        </dl>

                        <div id="backup-secondary">
                            <ul class="typecho-option-tabs">
                                <li class="active w-50"><a href="#from-upload">上传</a></li>
                                <li class="w-50"><a href="#from-server">从服务器</a></li>
                            </ul>

                            <form action="<?php echo $actionUrl; ?>" id="from-upload" class="tab-content" method="post" enctype="multipart/form-data">
                                <ul class="typecho-option">
                                    <li>
                                        <input tabindex="2" id="backup-upload-file" name="file" type="file" class="file">
                                    </li>
                                </ul>
                                <ul class="typecho-option typecho-option-submit">
                                    <li>
                                        <button tabindex="4" type="submit" class="btn primary">上传并恢复 &raquo;</button>
                                        <input type="hidden" name="do" value="import">
                                    </li>
                                </ul>
                            </form>

                            <form action="<?php echo $actionUrl; ?>" id="from-server" class="tab-content hidden" method="post">
                                <?php if (empty($backupFiles)): ?>
                                <ul class="typecho-option">
                                    <li>
                                        <p class="description">将备份文件手动上传至服务器的 <?php echo __TYPECHO_BACKUP_DIR__; ?> 目录下后, 这里会出现文件选项</p>
                                    </li>
                                </ul>
                                <?php else: ?>
                                <ul class="typecho-option">
                                    <li>
                                        <label class="typecho-label" for="backup-select-file">选择一个备份文件恢复数据</label>
                                        <select tabindex="5" name="file" id="backup-select-file">
                                            <?php foreach ($backupFiles as $file): ?>
                                                <option value="<?php echo $file; ?>"><?php echo $file; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </li>
                                </ul>
                                <?php endif; ?>
                                <ul class="typecho-option typecho-option-submit">
                                    <li>
                                        <button tabindex="7" type="submit" class="btn primary">选择并恢复 &raquo;</button>
                                        <input type="hidden" name="do" value="import">
                                    </li>
                                </ul>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ 大块二：Adminer 数据库备份 ============ -->
            <div class="col-mb-12 col-tb-6">
                <div class="bk-panel bk-panel-2">
                    <h3><span class="bk-no">2</span>Adminer 数据库备份（推荐）</h3>
                    <p class="bk-sub">
                        点下面的按钮会跳到本站内置的数据库管理工具，进去后由你自己手动导出整库。
                        <strong>这是唯一能带走全部设置和 Base64 配图的方式。</strong>
                    </p>

                    <div class="bk-alert bk-ok" style="margin-top:0;">
                        <strong>它会备份全部 8 张表</strong>，而且不只是数据：建表语句、索引、字段类型、
                        <strong>站点全部设置（options）</strong>、<strong>Base64 配图（images）</strong>、插件启用清单、站点密钥，一样不落。
                    </div>

                    <a class="btn primary bk-goto" href="<?php echo htmlspecialchars($adminerUrl); ?>" target="_blank" rel="noopener">打开 Adminer 备份整个数据库 &raquo;</a>

                    <div class="bk-usage">
                        <dl>
                            <dt>进去之后怎么手动备份</dt>
                            <dd>
                                <ol>
                                    <li>点开就已经在数据库里了，不用再填账号密码。左侧选中 <code>typecho20240517</code> 这个库。</li>
                                    <li>进入数据库后，点页面左上角菜单里的 <strong>Export / 导出</strong>。</li>
                                    <li>Output 选 <strong>save</strong>（直接下载文件），Format 保持 <strong>SQL</strong>。</li>
                                    <li>Database 选 <strong>DROP+CREATE</strong>，Tables 选 <strong>DROP+CREATE</strong>，Data 选 <strong>INSERT</strong>。</li>
                                    <li>下面表格里的<strong>所有表都要勾上</strong>（默认就是全勾），然后点 <strong>Export</strong> 就会下载一个 <code>.sql</code> 文件。</li>
                                </ol>
                            </dd>
                            <dt>恢复的时候</dt>
                            <dd>回到 Adminer，选择目标数据库后点 <strong>Import / 导入</strong>，上传那个 <code>.sql</code> 文件即可。
                                <span class="bk-red">导入同样是覆盖式的，动手前先导出一次当前数据。</span>
                            </dd>
                        </dl>
                        <p class="bk-hint bk-desc">
                            这个入口只对<strong>已登录的管理员</strong>开放，别人打不开；打开后不用再填数据库账号，直接就能管理。
                        </p>
                    </div>
                </div>
            </div>

            <!-- ============ 下面是详细说明，想弄清楚再往下看 ============ -->
            <div class="col-mb-12">
                <h3 style="margin:1.6em 0 .8em;font-size:1.3em;color:#333;">两种备份到底差在哪（详细说明）</h3>

                <div class="bk-alert bk-info">
                    <strong>一句话结论：</strong>程序自带备份只存<strong>「内容」</strong>（文章、评论、分类、用户、自定义字段），
                    不存<strong>「设置」</strong>；数据库备份是<strong>整库全量</strong>，把站点所有设置、配置、插件启用状态、密钥全部带走。
                    <br>要完整还原或搬迁整站，<strong>请以数据库备份为准</strong>。
                </div>

                <div class="bk-block" style="border:1px solid #D9534F;">
                    <h3 style="color:#B94A48;">最重要的一条：Base64 图片数据，到底会不会被备份？</h3>
                    <p>
                        本站的文章配图不是文件，而是以 <strong>Base64 纯文本</strong> 的形式存在数据库的
                        <code>images</code> 表里（<code>data</code> 字段，longtext 类型，原样保存整段编码）。
                        文章正文里只留一个引用记号，形如 <code>![联通流量配图][img1791618731689276]</code>；
                        打开文章时程序再按这个记号把图片数据取回来，换成真正的图片显示出来。
                        所以正文里看不到大段 Base64，但<strong>数据确实在数据库里</strong>。
                    </p>
                    <table class="bk-table">
                        <tr>
                            <th>备份方式</th>
                            <th>Base64 图片数据（images 表）</th>
                            <th>正文里的引用记号（contents 表）</th>
                            <th>恢复后图片还能显示吗</th>
                        </tr>
                        <tr>
                            <td class="bk-c1">程序自带备份（.dat）</td>
                            <td class="c-red"><strong>不备份</strong><br>images 表整个不在导出范围内</td>
                            <td class="c-green">备份</td>
                            <td class="c-red"><strong>不能。</strong>记号还在、图片数据丢了，正文里会直接露出 <code>![联通流量配图][img1791618731689276]</code> 这串文字</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">数据库备份（SQL 全量）</td>
                            <td class="c-green"><strong>完整备份</strong><br>data 是 longtext，导出时整段原样写出，一个字符都不截断</td>
                            <td class="c-green">备份</td>
                            <td class="c-green"><strong>能。</strong>导入后引用记号与图片数据重新对上，图片照常显示</td>
                        </tr>
                    </table>
                    <p class="bk-desc">
                        本站现状：图库共 <strong>3 张图</strong>（117KB / 56KB / 125KB 的 JPEG 原图，Base64 后合计 316,840 字节）；
                        正文引用了图片的文章共 <strong>4 篇</strong>，编号 944、1845、1850、1855。
                    </p>
                    <div class="bk-alert bk-ok">
                        <strong>已做实测（导出 → 导入往返比对）：</strong>数据库全量导出会把 Base64 图片一起带走，
                        导入到另一个库后与原库逐字节一致，图片数据的校验和完全相同。
                        结论：<span class="bk-ora">想保住图片，只能用数据库备份，程序自带备份一定会把图片弄丢。</span>
                    </div>
                </div>

                <div class="bk-block">
                    <h3>一、程序自带备份，到底备份了什么</h3>
                    <p class="bk-desc">来源：后台 <code>var/Widget/Backup.php</code>，导出为 <code>.dat</code> 文件（首尾带 <code>%TYPECHO_BACKUP_0001%</code> 标记）。</p>
                    <p><span class="bk-tag bk-tag-yes">会备份</span> 以下 <strong>6 张表</strong>，且是<strong>整表字段全存</strong>：</p>
                    <ul>
                        <li><strong>contents（文章 / 独立页面）</strong>：编号、标题、缩略名、创建时间、修改时间、<strong>正文全部内容</strong>、排序、作者编号、使用模板、类型、状态（发布/草稿/隐藏）、访问密码、评论数、是否允许评论、是否允许引用、是否允许订阅、父级编号。当前 <strong>1267 条</strong>。</li>
                        <li><strong>comments（评论）</strong>：编号、所属文章、时间、作者名、作者编号、内容作者、邮箱、网址、IP、浏览器标识、<strong>评论正文</strong>、类型、审核状态、父级评论。当前 <strong>55 条</strong>。</li>
                        <li><strong>metas（分类 / 标签）</strong>：编号、名称、缩略名、类型、描述、计数、排序、父级。当前 <strong>3 条</strong>。</li>
                        <li><strong>relationships（文章与分类/标签的对应关系）</strong>：文章编号、分类编号。当前 <strong>1254 条</strong>。</li>
                        <li><strong>users（用户）</strong>：编号、用户名、<strong>密码哈希</strong>、邮箱、网址、昵称、注册时间、最后活动时间、最后登录时间、用户组、<strong>authCode 登录凭据</strong>。当前 <strong>1 条</strong>。</li>
                        <li><strong>fields（文章自定义字段）</strong>：文章编号、字段名、字段类型、字符值、整数值、浮点值。当前 <strong>5 条</strong>。</li>
                    </ul>

                    <h4><span class="bk-tag bk-tag-no">不备份</span> 以下 2 张表（这是自带备份最大的缺口）</h4>
                    <ul>
                        <li><strong class="bk-red">options（站点全部设置与配置）</strong>：当前 <strong>67 条</strong>。凡是“你在后台点过保存”的东西基本都在这张表里：
                            <ul>
                                <li>站点信息：站点标题、描述、关键词、<strong>站点地址 siteUrl</strong>、时区、版本号、安装标记；</li>
                                <li>主题相关：<strong>当前启用的主题名</strong>，以及主题自己保存的<strong>全部主题配置项</strong>（<code>theme:default</code>）；</li>
                                <li>评论规则：头像开关与等级、是否必须审核、评论间隔限制、最大嵌套层数、是否允许 Markdown、允许的 HTML 标签、分页大小、排序方式、是否必须填邮箱/网址、是否开启反垃圾等 20 余项；</li>
                                <li>写作与内容：Markdown 开关、XML-RPC Markdown、自动保存、日期格式、每页文章数、默认是否允许评论/引用/订阅等；</li>
                                <li>地址与路由：<strong>固定链接（伪静态）开关</strong>、路由表、动作表、面板表、内容类型、允许上传的附件类型；</li>
                                <li>安全与接口：<strong>站点密钥 secret</strong>（后台所有操作令牌都由它签发）、XML-RPC 开关、是否允许注册、是否开启 Gzip；</li>
                                <li><strong>plugins（已启用的插件清单）</strong>；</li>
                                <li>用户个人偏好（<code>user=1</code> 的那几条）。</li>
                            </ul>
                        </li>
                        <li><strong class="bk-red">images（图库 / 文章配图）</strong>：图片编号、文件名、标题、MIME 类型、体积、宽高、<strong>图片数据本身（就是 Base64 纯文本，存在 data 字段里）</strong>、作者、时间。当前 <strong>3 条</strong>，Base64 文本合计 <strong>316,840 字节</strong>。</li>
                    </ul>

                    <h4><span class="bk-tag bk-tag-no">两种备份都不含</span> 的磁盘文件</h4>
                    <ul>
                        <li><code>usr/uploads/</code> 已上传的图片、附件等<strong>所有媒体文件</strong>；</li>
                        <li><code>usr/themes/</code> 主题文件、<code>usr/plugins/</code> 插件文件（只备份主题名和插件清单，不备份文件本身）；</li>
                        <li>站点根目录的 <code>favicon.ico</code>、<code>apple-touch-icon.png</code> 等图标文件；</li>
                        <li><code>config.inc.php</code>（数据库地址、账号、密码、表前缀等连接配置）。</li>
                    </ul>

                    <div class="bk-alert bk-warn">
                        <strong>注意：</strong>自带备份的 <code>.dat</code> 文件里含有<strong>用户密码哈希</strong>和 <strong>authCode 登录凭据</strong>，等同于一份“可登录凭证”，请勿放到公开可访问的目录或网盘分享。
                    </div>
                </div>

                <div class="bk-block">
                    <h3>二、直接用数据库备份，到底备份了什么</h3>
                    <p class="bk-desc">方式：用 phpMyAdmin / adminer 或 <code>mysqldump</code>，对数据库 <code>typecho20240517</code> 导出 SQL。</p>
                    <p><span class="bk-tag bk-tag-yes">会备份</span> <strong>全部 8 张表</strong>，而且不只是数据：</p>
                    <ul>
                        <li><strong>表结构</strong>：建表语句（CREATE TABLE）、字段类型、长度、默认值、注释、主键与索引；</li>
                        <li><strong>全部数据</strong>：8 张表一行不落，<strong>包含自带备份漏掉的 options 和 images</strong>；</li>
                        <li><strong>全部设置</strong>：站点标题、地址、时区、主题及其全部配置、评论规则、固定链接与路由表、附件类型、站点密钥 secret、插件启用清单、安装标记、用户个人偏好——全部在内；</li>
                        <li><strong>登录凭据</strong>：用户密码哈希与 authCode。</li>
                    </ul>
                    <p><span class="bk-tag bk-tag-no">同样不含</span> 上述所有磁盘文件（上传的图片、主题、插件、图标、config.inc.php）。<strong>数据库备份 ≠ 整站备份。</strong></p>

                    <div class="bk-alert bk-info">
                        <strong>操作入口：</strong>本站已装好 phpMyAdmin 与 adminer，可访问
                        <code>/phpmyadmin</code> 或 <code>/adminer.php</code>，选择数据库 <code>typecho20240517</code> 后点“导出”即可。
                        恢复时选择“导入”，上传 SQL 文件。
                    </div>
                </div>

                <div class="bk-block">
                    <h3>三、两种备份方式逐项对比</h3>
                    <table class="bk-table">
                        <tr>
                            <th>对比项</th>
                            <th>程序自带备份（.dat）</th>
                            <th>数据库备份（SQL 全量）</th>
                        </tr>
                        <tr>
                            <td class="bk-c1">备份范围</td>
                            <td>仅 <strong>6 张表</strong>：contents、comments、metas、relationships、users、fields</td>
                            <td class="c-green"><strong>全部 8 张表</strong>（另含 options、images）</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">站点设置 / 配置</td>
                            <td class="c-red"><strong>完全不备份</strong></td>
                            <td class="c-green"><strong>全部备份</strong></td>
                        </tr>
                        <tr>
                            <td class="bk-c1">主题与主题配置</td>
                            <td class="c-red">不含（连主题名都没有）</td>
                            <td class="c-green">含主题名 + 主题全部配置项</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">固定链接 / 路由表</td>
                            <td class="c-red">不含</td>
                            <td class="c-green">含（rewrite、routingTable、actionTable）</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">插件启用状态</td>
                            <td class="c-red">不含</td>
                            <td class="c-green">含（plugins 清单）</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">站点密钥 secret</td>
                            <td class="c-red">不含</td>
                            <td class="c-green">含</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">图库（images）</td>
                            <td class="c-red">不含</td>
                            <td class="c-green">含（含图片二进制数据）</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">Base64 图片数据（文章配图本体）</td>
                            <td class="c-red"><strong>不含</strong>，恢复后图片全部丢失</td>
                            <td class="c-green"><strong>完整含</strong>，longtext 原样导出不截断</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">表结构 / 索引</td>
                            <td class="c-red">不含（只能往已存在的表里插数据）</td>
                            <td class="c-green">含建表语句与索引，可重建整库</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">文章 / 评论 / 分类 / 用户</td>
                            <td class="c-green">含（字段全存）</td>
                            <td class="c-green">含</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">磁盘文件（图片、主题、插件、图标、配置文件）</td>
                            <td class="c-red">不含</td>
                            <td class="c-red">不含</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">文件体积</td>
                            <td class="c-green">较小（本例 2.19 MB，因为既没有设置也没有图片）</td>
                            <td>较大（本例 2.34 MB，结构与全部数据都在里面）</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">还原位置</td>
                            <td class="c-green">可在本页直接上传还原</td>
                            <td class="c-red">须到 phpMyAdmin / adminer 导入 SQL</td>
                        </tr>
                        <tr>
                            <td class="bk-c1">还原效果</td>
                            <td>只覆盖那 6 张表，<strong>设置保持现状不变</strong>（等于把内容搬进现有站点）</td>
                            <td>整库覆盖，<strong>设置一并回到备份时的状态</strong></td>
                        </tr>
                        <tr>
                            <td class="bk-c1">适合场景</td>
                            <td>只想搬内容、不想动设置；站点结构相同</td>
                            <td class="c-green"><strong>整站迁移、完整还原、长期归档（推荐）</strong></td>
                        </tr>
                    </table>

                    <div class="bk-alert bk-ok">
                        <strong>结论：数据库备份更全面。</strong>自带备份只能算是“内容备份”，数据库备份才是“站点数据全量备份”。
                        但因为两者都不含 <code>usr/uploads</code>、<code>usr/themes</code>、<code>usr/plugins</code> 等目录文件，
                        <span class="bk-ora">真正意义上的整站备份 = 数据库全量导出 + 站点目录整目录打包 + config.inc.php</span>，
                        三者缺一不可。
                    </div>
                </div>

                <div class="bk-block">
                    <h3>四、注意事项</h3>

                    <div class="bk-alert bk-danger">
                        <strong>危险 1｜恢复是覆盖式的，会清空数据，且不可撤销。</strong>
                        自带备份在导入时会对那 6 张表执行 <code>TRUNCATE</code>（清空）后再逐条写入；数据库备份导入时通常也是先删表再重建。
                        <strong>执行任何恢复之前，请务必先完整导出一份当前的数据库。</strong>
                    </div>

                    <div class="bk-alert bk-danger">
                        <strong>危险 2｜用自带备份恢复后，站点设置不会跟着回来。</strong>
                        站点标题、站点地址、时区、主题及其配置、固定链接与路由表、插件启用状态、评论规则、站点密钥 secret
                        <strong>全部会保持你当前站点的值</strong>，而不会变成备份文件里的值——因为这些东西压根不在备份文件里。
                    </div>

                    <div class="bk-alert bk-warn">
                        <strong>提醒 1｜数据库备份无法在本页直接还原。</strong>
                        本页的“上传并恢复 / 从服务器恢复”只认 <code>.dat</code> 格式。SQL 文件请到 phpMyAdmin 或 adminer 里导入。
                    </div>

                    <div class="bk-alert bk-warn">
                        <strong>提醒 2｜备份文件请妥善保管。</strong>
                        <code>.dat</code> 与 <code>.sql</code> 文件都含有用户密码哈希和登录凭据，且数据库备份还含有站点密钥 secret。
                        请勿放在网站可公开访问的目录，导出后建议尽快下载到本地并删除服务器上的临时文件。
                    </div>

                    <div class="bk-alert bk-warn">
                        <strong>提醒 3｜数据量大时不要用自带备份。</strong>
                        自带备份是 PHP 边读边写，数据量大时容易超时或内存溢出，此时请直接用数据库工具导出。
                    </div>

                    <div class="bk-alert bk-info">
                        <strong>提醒 4｜数据量很大时可以在导出前精简。</strong>
                        例如清空回收站、删除垃圾评论、清理无用草稿，都能明显减小备份体积。但这属于“先删后备份”，
                        <span class="bk-ora">精简前请一定先做一份完整备份</span>。
                    </div>

                    <div class="bk-alert bk-danger">
                        <strong>危险 3｜程序自带备份一定会把文章配图弄丢。</strong>
                        Base64 图片存在 <code>images</code> 表里，而这张表不在自带备份的 6 张表之内。
                        用 <code>.dat</code> 恢复后，正文里的 <code>![名称][imgXXXX]</code> 记号还在，但图片数据没了，
                        文章里会直接显示出这串记号文字。<strong>只要站内用过图库配图，就不要用自带备份来做迁移或还原。</strong>
                    </div>

                    <div class="bk-alert bk-info">
                        <strong>提醒 5｜注意图片与附件的去向（两类东西要分开记）。</strong>
                        一类是<strong>存在数据库里的 Base64 配图</strong>（<code>images</code> 表），只有数据库备份能带走；
                        另一类是 <code>usr/uploads/</code> 里的<strong>普通上传文件</strong>，任何数据库备份都带不走。
                        迁移时如果只搬数据库，正文能回来、Base64 配图也能回来，但 <code>usr/uploads/</code> 里的文件会全部 404，请连同目录一起打包。
                    </div>

                    <div class="bk-alert bk-warn">
                        <strong>提醒 6｜换回原版（未修改的）程序时，本数据库可以直接用，但有一点要注意。</strong>
                        本站的表结构<strong>没有任何改动</strong>（7 张标准表与原版逐字段一致），只是多出一张原版不认识的
                        <code>images</code> 图库表——它放着不碍事，原版不会报错。
                        但原版<strong>不认识正文里的 <code>![名称][imgXXXX]</code> 记号</strong>，
                        那 4 篇用了配图的文章在原版下会把这串记号原样显示出来。
                        另外原版没有动态站点地址特性，会直接使用数据库里的 <code>siteUrl</code>，换域名后要记得在后台改。
                    </div>

                    <div class="bk-alert bk-info">
                        <strong>提醒 7｜安装时用以前的数据库。</strong>
                        不需要走安装流程：只要 <code>config.inc.php</code> 里的库名、账号、密码、表前缀对得上，
                        程序读到数据库里的"已安装"标记就会直接当作已安装运行，旧数据原样可用。
                        如果确实要重装，安装器有两种模式：<strong>「新建数据库」</strong>填的库名已存在会直接报错，不会覆盖旧数据；
                        <strong>「从文件导入」</strong>则会把同名数据库
                        <span class="bk-red">整个删掉</span>再导入你上传的 SQL，用它恢复前请确认那个库确实可以丢。
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
?>
<script>
    $('#backup-secondary .typecho-option-tabs li').click(function() {
        $('#backup-secondary .typecho-option-tabs li').removeClass('active');
        $(this).addClass('active');
        $(this).parents('#backup-secondary').find('.tab-content').addClass('hidden');

        var selected_tab = $(this).find('a').attr('href');
        $(selected_tab).removeClass('hidden');

        return false;
    });

    $('#backup-secondary form').submit(function (e) {
        if (!confirm('恢复操作将清除所有现有数据, 是否继续?')) {
            return false;
        }
    });
</script>
<?php include 'footer.php'; ?>
