<?php

namespace Utils;

use Typecho\Db;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 文章配图仓储
 *
 * 图片以 base64 文本的形式存在独立的数据表里，正文里只保留一个引用标记
 * ![名称][imgXXXX]。渲染文章时按标记把图片数据取回来换成 <img>，
 * 编辑时正文里看到的始终只有这一个标记，不会出现大段 base64。
 */
class ImageStore
{
    /** 引用标记前缀 */
    public const PREFIX = 'img';

    /** 建表动作每个请求只做一次 */
    private static bool $ensured = false;

    /**
     * 图片表的真实表名（含前缀）
     */
    public static function table(): string
    {
        return Db::get()->getPrefix() . 'images';
    }

    /**
     * 确保数据表存在（懒执行）
     */
    public static function ensureTable(): void
    {
        if (self::$ensured) {
            return;
        }

        self::$ensured = true;

        $table = self::table();

        Db::get()->query(
            "CREATE TABLE IF NOT EXISTS `{$table}` ("
            . "`iid` int(10) unsigned NOT NULL AUTO_INCREMENT,"
            . "`name` varchar(64) NOT NULL,"
            . "`title` varchar(150) DEFAULT NULL,"
            . "`mime` varchar(50) NOT NULL DEFAULT 'image/jpeg',"
            . "`size` int(10) unsigned NOT NULL DEFAULT 0,"
            . "`width` int(10) unsigned NOT NULL DEFAULT 0,"
            . "`height` int(10) unsigned NOT NULL DEFAULT 0,"
            . "`data` longtext NOT NULL,"
            . "`authorId` int(10) unsigned NOT NULL DEFAULT 0,"
            . "`created` int(10) unsigned NOT NULL DEFAULT 0,"
            . "PRIMARY KEY (`iid`),"
            . "UNIQUE KEY `name` (`name`)"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            Db::WRITE
        );
    }

    /**
     * 解析 data:image/xxx;base64,xxxx 形式的数据
     *
     * @return array|null ['mime' => ..., 'data' => ..., 'size' => ...]
     */
    public static function parseDataUrl(string $dataUrl): ?array
    {
        if (!preg_match('#^\s*data:image/(png|jpe?g|gif|webp|bmp);base64,(.+)$#is', $dataUrl, $match)) {
            return null;
        }

        $data = preg_replace('/\s+/', '', $match[2]);

        if (empty($data) || false === base64_decode($data, true)) {
            return null;
        }

        $mime = 'image/' . strtolower($match[1]);
        $mime = 'image/jpg' === $mime ? 'image/jpeg' : $mime;

        return [
            'mime' => $mime,
            'data' => $data,
            'size' => (int) (strlen($data) * 3 / 4),
        ];
    }

    /**
     * 保存一张图片，返回引用名；失败返回 null
     */
    public static function put(string $dataUrl, ?string $title = '', array $extra = []): ?string
    {
        $parsed = self::parseDataUrl($dataUrl);

        if (empty($parsed)) {
            return null;
        }

        /** 兜底限制：单张图最大 2MB */
        if ($parsed['size'] > 2 * 1024 * 1024) {
            return null;
        }

        self::ensureTable();

        $db = Db::get();
        $name = self::PREFIX . time() . mt_rand(1000, 9999);

        $db->query($db->insert('table.images')->rows([
            'name'     => $name,
            'title'    => (string) $title,
            'mime'     => $parsed['mime'],
            'size'     => $parsed['size'],
            'width'    => (int) ($extra['width'] ?? 0),
            'height'   => (int) ($extra['height'] ?? 0),
            'data'     => $parsed['data'],
            'authorId' => (int) ($extra['authorId'] ?? 0),
            'created'  => time(),
        ]));

        return $name;
    }

    /**
     * 按引用名取图，返回 [name => ['mime'=>..,'data'=>..,'title'=>..]]
     */
    public static function getMany(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        try {
            self::ensureTable();

            $db = Db::get();
            $result = [];

            foreach ($names as $name) {
                $row = $db->fetchRow(
                    $db->select('name', 'mime', 'data', 'title')
                        ->from('table.images')
                        ->where('name = ?', $name)
                );

                if (!empty($row)) {
                    $result[$row['name']] = $row;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            /** 取图失败绝不影响文章本身 */
            return [];
        }
    }

    /**
     * 把正文里的 ![名称][imgXXXX] 换成真正的图片，并清掉遗留的 base64 定义
     */
    public static function resolve(?string $text): ?string
    {
        if (empty($text) || false === strpos($text, '][')) {
            return $text;
        }

        $pattern = '/!\[([^\]]*)\]\[(' . self::PREFIX . '[A-Za-z0-9_]+)\]/u';

        if (!preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            return $text;
        }

        $names = [];

        foreach ($matches as $match) {
            $names[$match[2]] = true;
        }

        $images = self::getMany(array_keys($names));

        if (empty($images)) {
            return $text;
        }

        $replace = [];
        $used = [];

        foreach ($matches as $match) {
            $name = $match[2];

            if (!isset($images[$name])) {
                continue;
            }

            $used[$name] = true;

            $replace[$match[0]] = '<img src="data:' . $images[$name]['mime'] . ';base64,'
                . $images[$name]['data'] . '" alt="'
                . htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8') . '" class="stored-image" />';
        }

        if (empty($replace)) {
            return $text;
        }

        $text = strtr($text, $replace);

        /** 清掉旧写法遗留在正文里的 [imgXXXX]: data:image/...;base64,xxx 定义 */
        foreach (array_keys($used) as $name) {
            $text = preg_replace(
                '/\[[ \t]*' . preg_quote($name, '/') . '[ \t]*\]:[ \t]*data:image\/[a-z0-9.+-]+;base64,[A-Za-z0-9+\/=]+/i',
                '',
                $text
            );
        }

        return $text;
    }
}
