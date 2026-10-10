<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Db\Exception as DbException;
use Typecho\Widget;
use Utils\PasswordHash;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 当前登录用户
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class User extends Users
{
    /**
     * 登录保持时长(秒)
     *
     * 登录状态不再随浏览器会话结束而失效, 也不会因为其它设备登录而被踢下线
     */
    public const PERSIST_EXPIRE = 365 * 24 * 3600;

    /**
     * 用户组
     *
     * @var array
     */
    public array $groups = [
        'administrator' => 0,
        'editor' => 1,
        'contributor' => 2,
        'subscriber' => 3,
        'visitor' => 4
    ];

    /**
     * 用户
     *
     * @var array
     */
    private array $currentUser;

    /**
     * 是否已经登录
     *
     * @var boolean|null
     */
    private ?bool $hasLogin = null;

    /**
     * @param int $components
     */
    protected function initComponents(int &$components)
    {
        $components = self::INIT_OPTIONS;
    }

    /**
     * 执行函数
     *
     * @throws DbException
     */
    public function execute()
    {
        if ($this->hasLogin()) {
            $this->push($this->currentUser);

            // update last activated time
            $this->db->query($this->db
                ->update('table.users')
                ->rows(['activated' => $this->options->time])
                ->where('uid = ?', $this->currentUser['uid']));

            // merge personal options
            $options = $this->personalOptions->toArray();

            foreach ($options as $key => $val) {
                $this->options->{$key} = $val;
            }
        }
    }

    /**
     * 判断用户是否已经登录
     *
     * @return boolean
     * @throws DbException
     */
    public function hasLogin(): ?bool
    {
        if (null !== $this->hasLogin) {
            return $this->hasLogin;
        } else {
            $cookieUid = Cookie::get('__typecho_uid');
            if (null !== $cookieUid) {
                /** 验证登录 */
                $user = $this->db->fetchRow($this->db->select()->from('table.users')
                    ->where('uid = ?', intval($cookieUid))
                    ->limit(1));

                $cookieAuthCode = Cookie::get('__typecho_authCode');
                if ($user && Common::hashValidate($user['authCode'], $cookieAuthCode)) {
                    $this->currentUser = $user;
                    return ($this->hasLogin = true);
                }

                $this->logout();
            }

            return ($this->hasLogin = false);
        }
    }

    /**
     * 用户登出函数
     *
     * @access public
     * @return void
     */
    public function logout()
    {
        self::pluginHandle()->trigger($logoutPluggable)->call('logout');
        if ($logoutPluggable) {
            return;
        }

        Cookie::delete('__typecho_uid');
        Cookie::delete('__typecho_authCode');
    }

    /**
     * 以用户名和密码登录
     *
     * @access public
     * @param string $name 用户名
     * @param string $password 密码
     * @param boolean $temporarily 是否为临时登录
     * @param integer $expire 过期时间
     * @return boolean
     * @throws DbException
     */
    public function login(string $name, string $password, bool $temporarily = false, int $expire = 0): bool
    {
        //插件接口
        $result = self::pluginHandle()->trigger($loginPluggable)->call('login', $name, $password, $temporarily, $expire);
        if ($loginPluggable) {
            return $result;
        }

        /** 开始验证用户 **/
        $user = $this->db->fetchRow($this->db->select()
            ->from('table.users')
            ->where('name = ?', $name)
            ->limit(1));

        if (empty($user) && strpos($name, '@') !== false) {
            $user = $this->db->fetchRow($this->db->select()
                ->from('table.users')
                ->where('mail = ?', $name)
                ->limit(1));
        }

        if (empty($user)) {
            return false;
        }

        $hashValidate = self::pluginHandle()->trigger($hashPluggable)->call('hashValidate', $password, $user['password']);
        if (!$hashPluggable) {
            if ('$P$' == substr($user['password'], 0, 3)) {
                $hasher = new PasswordHash(8, true);
                $hashValidate = $hasher->checkPassword($password, $user['password']);
            } else {
                $hashValidate = Common::hashValidate($password, $user['password']);
            }
        }

        if ($hashValidate) {
            if (!$temporarily) {
                $this->commitLogin($user, $expire);
            }

            /** 压入数据 */
            $this->push($user);
            $this->currentUser = $user;
            $this->hasLogin = true;
            self::pluginHandle()->call('loginSucceed', $this, $name, $password, $temporarily, $expire);

            return true;
        }

        self::pluginHandle()->call('loginFail', $this, $name, $password, $temporarily, $expire);
        return false;
    }

    /**
     * @param $user
     * @param int $expire
     * @throws DbException
     */
    public function commitLogin(&$user, int $expire = 0)
    {
        /**
         * 仅在账号还没有 authCode 时生成, 已有则原样沿用
         *
         * 原逻辑每次登录都会重新生成 authCode 并写回数据库,
         * 而登录校验是拿 Cookie 里的 authCode 与数据库里的 authCode 比对,
         * 因此只要有新设备登录, 旧设备的 Cookie 就会立刻对不上而失效。
         * 改为沿用已有 authCode 后, 同一账号可以在多台设备/多个浏览器上同时保持登录。
         */
        $authCode = empty($user['authCode']) ?
            (function_exists('openssl_random_pseudo_bytes') ?
                bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Common::randString(20)))
            : $user['authCode'];
        $user['authCode'] = $authCode;

        /** 登录状态长期保持, 取"调用方指定时长"与"长期时长"中的较大者 */
        $cookieExpire = max($expire, self::PERSIST_EXPIRE);

        Cookie::set('__typecho_uid', $user['uid'], $cookieExpire);
        Cookie::set('__typecho_authCode', Common::hash($authCode), $cookieExpire);

        //更新最后登录时间以及验证码
        $this->db->query($this->db
            ->update('table.users')
            ->expression('logged', 'activated')
            ->rows(['authCode' => $authCode])
            ->where('uid = ?', $user['uid']));
    }

    /**
     * 只需要提供uid或者完整user数组即可登录的方法, 多用于插件等特殊场合
     *
     * @param int | array $uid 用户id或者用户数据数组
     * @param boolean $temporarily 是否为临时登录，默认为临时登录以兼容以前的方法
     * @param integer $expire 过期时间
     * @return boolean
     * @throws DbException
     */
    public function simpleLogin($uid, bool $temporarily = true, int $expire = 0): bool
    {
        if (is_array($uid)) {
            $user = $uid;
        } else {
            $user = $this->db->fetchRow($this->db->select()
                ->from('table.users')
                ->where('uid = ?', $uid)
                ->limit(1));
        }

        if (empty($user)) {
            self::pluginHandle()->call('simpleLoginFail', $this);
            return false;
        }

        if (!$temporarily) {
            $this->commitLogin($user, $expire);
        }

        $this->push($user);
        $this->currentUser = $user;
        $this->hasLogin = true;

        self::pluginHandle()->call('simpleLoginSucceed', $this, $user);
        return true;
    }

    /**
     * 判断用户权限
     *
     * @access public
     * @param string $group 用户组
     * @param boolean $return 是否为返回模式
     * @return boolean
     * @throws DbException|Widget\Exception
     */
    public function pass(string $group, bool $return = false): bool
    {
        if ($this->hasLogin()) {
            if (array_key_exists($group, $this->groups) && $this->groups[$this->group] <= $this->groups[$group]) {
                return true;
            }
        } else {
            if ($return) {
                return false;
            } else {
                //防止循环重定向
                $this->response->redirect(defined('__TYPECHO_ADMIN__') ? $this->options->loginUrl .
                    (0 === strpos($this->request->getReferer() ?? '', $this->options->loginUrl) ? '' :
                        '?referer=' . urlencode($this->request->makeUriByRequest())) : $this->options->siteUrl);
            }
        }

        if ($return) {
            return false;
        } else {
            throw new Widget\Exception(_t('禁止访问'), 403);
        }
    }
}
