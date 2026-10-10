<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php $content = !empty($post) ? $post : $page; ?>
<script>
(function () {
    $('#text').on('change', function (e) {
        e.preventDefault();
        e.stopPropagation();
    }).on('input', function () {
        $(this).parents('form').trigger('write');
    });
})();
</script>
<?php if (!$options->markdown): ?>
<script>
(function () {
    const textarea = $('#text');

    // 原始的插入图片和文件
    Typecho.insertFileToEditor = function (file, url, isImage) {
        const sel = textarea.getSelection(),
            html = isImage ? '<img src="' + url + '" alt="' + file + '" />'
                : '<a href="' + url + '">' + file + '</a>',
            offset = (sel ? sel.start : 0) + html.length;

        textarea.replaceSelection(html);
        textarea.setSelection(offset, offset);
    };
})();
</script>
<?php else: ?>
<script src="<?php $options->adminStaticUrl('js', 'hyperdown.js'); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'pagedown.js'); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'purify.js'); ?>"></script>
<script>
$(document).ready(function () {
    const textarea = $('#text'),
        toolbar = $('<div class="editor" id="wmd-button-bar" />').insertBefore(textarea.parent()),
        preview = $('<div id="wmd-preview" class="wmd-hidetab" />').insertAfter('.editor');
    let isFullScreen = false;

    const options = {}, isMarkdown = <?php echo json_encode(!$content->have() || $content->isMarkdown); ?>;

    options.strings = {
        bold: '<?php _e('加粗'); ?> <strong> Ctrl+B',
        boldexample: '<?php _e('加粗文字'); ?>',
            
        italic: '<?php _e('斜体'); ?> <em> Ctrl+I',
        italicexample: '<?php _e('斜体文字'); ?>',

        link: '<?php _e('链接'); ?> <a> Ctrl+L',
        linkdescription: '<?php _e('请输入链接描述'); ?>',

        quote:  '<?php _e('引用'); ?> <blockquote> Ctrl+Q',
        quoteexample: '<?php _e('引用文字'); ?>',

        code: '<?php _e('代码'); ?> <pre><code> Ctrl+K',
        codeexample: '<?php _e('请输入代码'); ?>',

        image: '<?php _e('图片'); ?> <img> Ctrl+G',
        imagedescription: '<?php _e('请输入图片描述'); ?>',

        olist: '<?php _e('数字列表'); ?> <ol> Ctrl+O',
        ulist: '<?php _e('普通列表'); ?> <ul> Ctrl+U',
        litem: '<?php _e('列表项目'); ?>',

        heading: '<?php _e('标题'); ?> <h1>/<h2> Ctrl+H',
        headingexample: '<?php _e('标题文字'); ?>',

        hr: '<?php _e('分割线'); ?> <hr> Ctrl+R',
        more: '<?php _e('摘要分割线'); ?> <!--more--> Ctrl+M',

        undo: '<?php _e('撤销'); ?> - Ctrl+Z',
        redo: '<?php _e('重做'); ?> - Ctrl+Y',
        redomac: '<?php _e('重做'); ?> - Ctrl+Shift+Z',

        fullscreen: '<?php _e('全屏'); ?> - Ctrl+J',
        exitFullscreen: '<?php _e('退出全屏'); ?> - Ctrl+E',
        fullscreenUnsupport: '<?php _e('此浏览器不支持全屏操作'); ?>',

        imagedialog: '<p><b><?php _e('插入图片'); ?></b></p><p><?php _e('请在下方的输入框内输入要插入的远程图片地址'); ?></p><p><?php _e('您也可以使用附件功能插入上传的本地图片'); ?></p>',
        linkdialog: '<p><b><?php _e('插入链接'); ?></b></p><p><?php _e('请在下方的输入框内输入要插入的链接地址'); ?></p>',

        ok: '<?php _e('确定'); ?>',
        cancel: '<?php _e('取消'); ?>',

        help: '<?php _e('Markdown语法帮助'); ?>'
    };

    const converter = new HyperDown(),
        editor = new Markdown.Editor(converter, '', options);

    // 自动跟随
    converter.enableHtml(true);
    converter.enableLine(true);
    const reloadScroll = scrollableEditor(textarea, preview);

    // 修正白名单
    converter.hook('makeHtml', function (html) {
        html = html.replace('<p><!--more--></p>', '<!--more-->');
        
        if (html.indexOf('<!--more-->') > 0) {
            var parts = html.split(/\s*<\!\-\-more\-\->\s*/),
                summary = parts.shift(),
                details = parts.join('');

            html = '<div class="summary">' + summary + '</div>'
                + '<div class="details">' + details + '</div>';
        }

        // 替换block
        html = html.replace(/<(iframe|embed)\s+([^>]*)>/ig, function (all, tag, src) {
            if (src[src.length - 1] === '/') {
                src = src.substring(0, src.length - 1);
            }

            return '<div class="embed"><strong>'
                + tag + '</strong> : ' + $.trim(src) + '</div>';
        });

        return DOMPurify.sanitize(html, {USE_PROFILES: {html: true}});
    });

    editor.hooks.chain('onPreviewRefresh', function () {
        const images = $('img', preview);
        let count = images.length;

        if (count === 0) {
            reloadScroll(true);
        } else {
            images.bind('load error', function () {
                count --;

                if (count === 0) {
                    reloadScroll(true);
                }
            });
        }
    });

    <?php \Typecho\Plugin::factory('admin/editor-js.php')->call('markdownEditor', $content); ?>

    let th = textarea.height(), ph = preview.height();
    const uploadBtn = $('<button type="button" id="btn-fullscreen-upload" class="btn btn-link">'
            + '<i class="i-upload"><?php _e('附件'); ?></i></button>')
            .prependTo('.submit .right')
            .click(function() {
                $('a', $('.typecho-option-tabs li').not('.active')).trigger('click');
                return false;
            });

    $('.typecho-option-tabs li').click(function () {
        uploadBtn.find('i').toggleClass('i-upload-active',
            $('#tab-files-btn', this).length > 0);
    });

    editor.hooks.chain('enterFakeFullScreen', function () {
        th = textarea.height();
        ph = preview.height();
        $(document.body).addClass('fullscreen');
        const h = $(window).height() - toolbar.outerHeight();
        
        textarea.css('height', h);
        preview.css('height', h);
        isFullScreen = true;
    });

    editor.hooks.chain('enterFullScreen', function () {
        $(document.body).addClass('fullscreen');
        
        const h = window.screen.height - toolbar.outerHeight();
        textarea.css('height', h);
        preview.css('height', h);
        isFullScreen = true;
    });

    editor.hooks.chain('exitFullScreen', function () {
        $(document.body).removeClass('fullscreen');
        textarea.height(th);
        preview.height(ph);
        isFullScreen = false;
    });

    editor.hooks.chain('commandExecuted', function () {
        textarea.trigger('input');
    });

    editor.hooks.chain('save', function () {
        Typecho.savePost();
    });

    function initMarkdown() {
        editor.run();

        // ===== 工具栏自定义改造 开始 =====

        // 1) 移除 加粗 / 斜体 / 链接 / 引用 四个按钮
        $('#wmd-bold-button, #wmd-italic-button, #wmd-link-button, #wmd-quote-button').remove();

        // 1.1) 同时屏蔽 Ctrl+B / Ctrl+I / Ctrl+L / Ctrl+Q 快捷键（pagedown 仍保留这些命令）
        textarea.get(0).addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && !e.altKey && !e.shiftKey) {
                const key = String.fromCharCode(e.charCode || e.keyCode).toLowerCase();
                if (key === 'b' || key === 'i' || key === 'l' || key === 'q') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }
        }, true);

        // 2) 光标处插入整段文本（替换选中内容）
        function insertPlain(snippet) {
            const sel = textarea.getSelection(),
                value = textarea.val(),
                start = sel ? sel.start : value.length,
                end = sel ? sel.end : start;

            textarea.val(value.substring(0, start) + snippet + value.substring(end));

            const cursor = start + snippet.length;
            textarea.setSelection(cursor, cursor);
            textarea.trigger('input');
            textarea.focus();
        }

        // 3) 用标签包裹选中内容；无选中则插入空标签并把光标放进中间
        function wrapWithTag(before, after) {
            const sel = textarea.getSelection(),
                value = textarea.val(),
                start = sel ? sel.start : value.length,
                end = sel ? sel.end : start,
                selected = value.substring(start, end);

            textarea.val(value.substring(0, start) + before + selected + after + value.substring(end));

            const cursor = start + before.length;
            textarea.setSelection(cursor, cursor + selected.length);
            textarea.trigger('input');
            textarea.focus();
        }

        function pad2(n) {
            return (n < 10 ? '0' : '') + n;
        }

        // 4) 在光标处插入图片引用标记
        //    （图片数据已经单独存进数据库，正文里只留 ![名称][imgXXXX]）
        function insertImageToken(key, alt) {
            const value = textarea.val(),
                sel = textarea.getSelection(),
                start = sel ? sel.start : value.length,
                end = sel ? sel.end : start,
                before = (start > 0 && value[start - 1] !== '\n') ? '\n\n' : '',
                after = (value[end] && value[end] !== '\n') ? '\n\n' : '',
                token = before + '![' + alt + '][' + key + ']' + after;

            textarea.val(value.substring(0, start) + token + value.substring(end));

            // 光标停在刚插入的图片标记之后
            const cursor = start + token.length;
            textarea.setSelection(cursor, cursor);
            textarea.trigger('input');
            textarea.focus();
        }

        // 5) 往工具栏插入自定义按钮（统一插在“图片”按钮右侧，确保一定可见）
        let lastCustomButton = null;

        function addButton(id, label, title, color, handler) {
            const span = $('<span></span>').text(label).css({
                display: 'block',
                width: 'auto',
                minWidth: '20px',
                height: '20px',
                padding: '0 5px',
                'line-height': '20px',
                'font-size': '12px',
                'font-weight': 'bold',
                'background-image': 'none',
                'box-sizing': 'border-box',
                'text-align': 'center',
                color: color
            });

            const button = $('<li class="wmd-button"></li>')
                .attr('id', id)
                .attr('title', title)
                .append(span)
                .on('click', function (e) {
                    e.preventDefault();
                    handler();
                    return false;
                });

            const anchor = lastCustomButton || $('#wmd-image-button');

            if (anchor && anchor.length) {
                button.insertAfter(anchor);
            } else {
                button.appendTo('.wmd-button-row');
            }

            lastCustomButton = button;
        }

        // 6) 图片按钮：改为直接选择本地图片，压缩后转 base64 放到正文末尾
        const imageFileInput = $('<input type="file" accept="image/*" style="display:none">')
            .appendTo(document.body);

        $('#wmd-image-button').attr('title', '<?php _e('插入本地图片'); ?>')
            .get(0).onclick = function () {
                imageFileInput.val('');
                imageFileInput.trigger('click');
                return false;
            };

        function base64Bytes(dataUrl) {
            const b64 = dataUrl.substring(dataUrl.indexOf(',') + 1);
            let padding = 0;

            if (b64.endsWith('==')) {
                padding = 2;
            } else if (b64.endsWith('=')) {
                padding = 1;
            }

            return Math.floor(b64.length * 3 / 4) - padding;
        }

        // 压缩 + 缩放：尺寸按原图比例自适应收缩，直到体积小于 maxBytes
        function compressImage(img, maxBytes) {
            const canvas = document.createElement('canvas'),
                ctx = canvas.getContext('2d');
            let w = img.naturalWidth || img.width,
                h = img.naturalHeight || img.height;

            const maxDim = 1600;
            if (Math.max(w, h) > maxDim) {
                const ratio = maxDim / Math.max(w, h);
                w = Math.max(1, Math.round(w * ratio));
                h = Math.max(1, Math.round(h * ratio));
            }

            let dataUrl = '';
            for (let round = 0; round < 24; round++) {
                canvas.width = w;
                canvas.height = h;
                ctx.clearRect(0, 0, w, h);
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);

                for (let quality = 0.92; quality >= 0.3; quality -= 0.08) {
                    dataUrl = canvas.toDataURL('image/jpeg', quality);

                    if (base64Bytes(dataUrl) <= maxBytes) {
                        return dataUrl;
                    }
                }

                w = Math.max(1, Math.round(w * 0.85));
                h = Math.max(1, Math.round(h * 0.85));

                if (w <= 20 || h <= 20) {
                    break;
                }
            }

            return dataUrl;
        }

        imageFileInput.on('change', function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            const alt = ((file.name || '').replace(/\.[^.]+$/, '') || '<?php _e('图片'); ?>');

            const reader = new FileReader();
            reader.onload = function (ev) {
                const img = new Image();

                img.onload = function () {
                    const dataUrl = compressImage(img, 50 * 1024);

                    // 图片本身存数据库，正文里只写一个引用标记
                    $.ajax({
                        url: '<?php $security->index('/action/ajax?do=imageStore'); ?>',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            data: dataUrl,
                            title: file.name || '',
                            width: img.naturalWidth || 0,
                            height: img.naturalHeight || 0
                        }
                    }).done(function (res) {
                        if (res && res.ok && res.key) {
                            insertImageToken(res.key, alt);
                        } else {
                            alert((res && res.message) || '<?php _e('图片保存失败'); ?>');
                        }
                    }).fail(function () {
                        alert('<?php _e('图片保存失败, 请重试'); ?>');
                    });
                };

                img.onerror = function () {
                    alert('<?php _e('图片读取失败, 请换一张试试'); ?>');
                };

                img.src = ev.target.result;
            };

            reader.readAsDataURL(file);
        });

        // 7) 新增按钮：T（当前时间）/ Red（红字）/ Pur（紫字）
        addButton('wmd-timestamp-button', 'T', '<?php _e('插入当前时间'); ?>', '#444',
            function () {
                const d = new Date();
                insertPlain(d.getFullYear() + '年' + (d.getMonth() + 1) + '月' + d.getDate() + '日'
                    + pad2(d.getHours()) + ':' + pad2(d.getMinutes()) + ':' + pad2(d.getSeconds()));
            });

        addButton('wmd-red-button', 'Red', '<?php _e('插入红色文字'); ?>', '#D9534F',
            function () {
                wrapWithTag('<font color="red">', '</font>');
            });

        addButton('wmd-purple-button', 'Pur', '<?php _e('插入紫色文字'); ?>', '#8A4FBD',
            function () {
                wrapWithTag('<font color="purple">', '</font>');
            });

        // 8) 新建文章时，正文开头自动插入当前详细日期，并把光标落到下一行
        const isNewPost = <?php echo json_encode(!empty($post) && !$content->have()); ?>;

        if (isNewPost && $.trim(textarea.val()) === '') {
            const now = new Date(),
                stamp = now.getFullYear() + '年' + (now.getMonth() + 1) + '月' + now.getDate() + '日'
                    + pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());

            textarea.val(stamp + '\n');
            textarea.setSelection(stamp.length + 1, stamp.length + 1);
            textarea.trigger('input');
            textarea.focus();
        }

        // ===== 工具栏自定义改造 结束 =====

        Typecho.insertFileToEditor = function (file, url, isImage) {
            insertPlain((isImage ? '![' : '[') + file + '](' + url + ')');
        };

        Typecho.uploadComplete = function (attachment) {
            Typecho.insertFileToEditor(attachment.title, attachment.url, attachment.isImage);
        };

        // 编辑预览切换
        const edittab = $('.editor').append('<div class="wmd-edittab"><a href="#wmd-editarea" class="active"><?php _e('撰写'); ?></a><a href="#wmd-preview"><?php _e('预览'); ?></a></div>'),
            editarea = $(textarea.parent()).attr("id", "wmd-editarea");

        $(".wmd-edittab a").click(function() {
            $(".wmd-edittab a").removeClass('active');
            $(this).addClass("active");
            $("#wmd-editarea, #wmd-preview").addClass("wmd-hidetab");
        
            const selected_tab = $(this).attr("href"),
                selected_el = $(selected_tab).removeClass("wmd-hidetab");

            // 预览时隐藏编辑器按钮
            if (selected_tab === "#wmd-preview") {
                $("#wmd-button-row").addClass("wmd-visualhide");
            } else {
                $("#wmd-button-row").removeClass("wmd-visualhide");
            }

            // 预览和编辑窗口高度一致
            $("#wmd-preview").outerHeight($("#wmd-editarea").innerHeight());

            return false;
        });

        // 剪贴板复制图片
        textarea.bind('paste', function (e) {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;

            for (const item of items) {
                if (item.kind === 'file') {
                    const file = item.getAsFile();

                    if (file.size > 0) {
                        if (!file.name) {
                            file.name = (new Date()).toISOString().replace(/\..+$/, '')
                                + '.' + file.type.split('/').pop();
                        }

                        Typecho.uploadFile(file);
                    }
                }
            }
        });
    }

    // 所有文章统一使用 Markdown 编辑器（保证工具栏功能一致）
    initMarkdown();
});
</script>
<?php endif; ?>

