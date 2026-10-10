<?php

namespace Widget;

use Typecho\Common;
use Typecho\Response;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 安全选项组件
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2014 Typecho team (http://typecho.org)
 * @license GNU General Public License 2.0
 */
class Security extends Base
{
    /**
     * @var string
     */
    private string $token;

    /**
     * @var boolean
     */
    private bool $enabled = true;

    /**
     * @param int $components
     */
    public function initComponents(int &$components)
    {
        $components = self::INIT_OPTIONS | self::INIT_USER;
    }

    /**
     * 初始化函数
     */
    public function execute()
    {
        $this->token = $this->options->secret;
        if ($this->user->hasLogin()) {
            $this->token .= '&' . $this->user->authCode . '&' . $this->user->uid;
        }
    }

    /**
     * @param bool $enabled
     */
    public function enable(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * 保护提交数据
     */
    public function protect()
    {
        if (!$this->enabled) {
            return;
        }

        $token = $this->request->get('_');

        if (is_string($token) && '' !== $token) {
            // 1) 兼容原有方式: 以来源页(Referer)计算
            if (hash_equals($this->getToken($this->request->getReferer()), $token)) {
                return;
            }

            // 2) 以目标地址本身计算, 不依赖 Referer
            if (hash_equals($this->getToken($this->getRequestSignature()), $token)) {
                return;
            }
        }

        $this->response->goBack();
    }

    /**
     * 当前请求地址(路径 + 查询串)的签名基准, 剔除 token 参数本身
     *
     * @return string|null
     */
    private function getRequestSignature(): ?string
    {
        $uri = $this->request->getRequestUri();

        if (empty($uri)) {
            return null;
        }

        $path = $uri;
        $query = '';

        if (false !== ($pos = strpos($uri, '?'))) {
            $path = substr($uri, 0, $pos);
            $query = substr($uri, $pos + 1);
        }

        $params = [];
        if ('' !== $query) {
            parse_str($query, $params);
        }
        unset($params['_']);

        return $this->buildSignature($path, $params);
    }

    /**
     * 生成签名基准
     *
     * @param string $path 路径
     * @param array $params 查询参数
     * @return string
     */
    private function buildSignature(string $path, array $params): string
    {
        $query = http_build_query($params);
        return $path . ('' === $query ? '' : '?' . $query);
    }

    /**
     * 获取token
     *
     * @param string|null $suffix 后缀
     * @return string
     */
    public function getToken(?string $suffix): string
    {
        return md5($this->token . '&' . $suffix);
    }

    /**
     * 获取绝对路由路径
     *
     * @param string|null $path
     * @return string
     */
    public function getRootUrl(?string $path): string
    {
        return $this->getTokenUrl(Common::url($path, $this->options->rootUrl));
    }

    /**
     * 生成带token的路径
     *
     * @param $path
     * @param string|null $url
     * @return string
     */
    public function getTokenUrl($path, ?string $url = null): string
    {
        $parts = parse_url($path);
        $params = [];

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        if (null === $url) {
            // 默认绑定目标地址本身, 不依赖 Referer (手机浏览器常丢失 Referer 导致校验失败)
            $suffix = $this->buildSignature($parts['path'] ?? '', $params);
        } else {
            $suffix = $url;
        }

        $params['_'] = $this->getToken($suffix);
        $parts['query'] = http_build_query($params);

        return Common::buildUrl($parts);
    }

    /**
     * 输出后台安全路径
     *
     * @param $path
     */
    public function adminUrl($path)
    {
        echo $this->getAdminUrl($path);
    }

    /**
     * 获取安全的后台路径
     *
     * @param string $path
     * @return string
     */
    public function getAdminUrl(string $path): string
    {
        return $this->getTokenUrl(Common::url($path, $this->options->adminUrl));
    }

    /**
     * 输出安全的路由路径
     *
     * @param $path
     */
    public function index($path)
    {
        echo $this->getIndex($path);
    }

    /**
     * 获取安全的路由路径
     *
     * @param $path
     * @return string
     */
    public function getIndex($path): string
    {
        return $this->getTokenUrl(Common::url($path, $this->options->index));
    }
}
 
