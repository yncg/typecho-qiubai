<?php

namespace Widget;

use Typecho\Widget\Exception;
use Widget\Base\Options as BaseOptions;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 异步调用组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Ajax extends BaseOptions implements ActionInterface
{
    /**
     * 针对rewrite验证的请求返回
     *
     * @access public
     * @return void
     */
    public function remoteCallback()
    {
        if ($this->options->generator == $this->request->getAgent()) {
            echo 'OK';
        }
    }

    /**
     * 获取最新版本（离线版：不再请求外网，永远返回无更新）
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function checkVersion()
    {
        $this->user->pass('editor');
        $this->response->throwJson(['available' => 0]);
    }

    /**
     * 远程请求代理（离线版：不再请求外网，直接返回空数据）
     *
     * @throws Exception
     * @throws \Typecho\Db\Exception
     */
    public function feed()
    {
        $this->user->pass('subscriber');
        $this->response->throwJson([]);
    }

    /**
     * 自定义编辑器大小
     *
     * @throws \Typecho\Db\Exception|Exception
     */
    public function editorResize()
    {
        $this->user->pass('contributor');
        $size = $this->request->filter('int')->get('size');

        if (
            $this->db->fetchObject($this->db->select(['COUNT(*)' => 'num'])
                ->from('table.options')->where('name = ? AND user = ?', 'editorSize', $this->user->uid))->num > 0
        ) {
            parent::update(
                ['value' => $size],
                $this->db->sql()->where('name = ? AND user = ?', 'editorSize', $this->user->uid)
            );
        } else {
            parent::insert([
                'name'  => 'editorSize',
                'value' => $size,
                'user'  => $this->user->uid
            ]);
        }
    }

    /**
     * 把编辑器里选中的本地图片存进数据库，返回引用名
     * （正文里只保留 ![名称][imgXXXX] 这个标记，图片数据单独入库）
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function imageStore()
    {
        $this->user->pass('contributor');

        $dataUrl = (string) $this->request->get('data');
        $title = (string) $this->request->get('title');

        $key = \Utils\ImageStore::put($dataUrl, $title, [
            'width'    => (int) $this->request->get('width'),
            'height'   => (int) $this->request->get('height'),
            'authorId' => $this->user->uid,
        ]);

        if (empty($key)) {
            $this->response->throwJson(['ok' => 0, 'message' => _t('图片格式不支持或体积过大')]);
        }

        $this->response->throwJson(['ok' => 1, 'key' => $key]);
    }

    /**
     * 异步请求入口
     *
     * @access public
     * @return void
     */
    public function action()
    {
        if (!$this->request->isAjax()) {
            $this->response->goBack();
        }

        $this->on($this->request->is('do=remoteCallback'))->remoteCallback();
        $this->on($this->request->is('do=feed'))->feed();
        $this->on($this->request->is('do=checkVersion'))->checkVersion();
        $this->on($this->request->is('do=editorResize'))->editorResize();
        $this->on($this->request->is('do=imageStore'))->imageStore();
    }
}
