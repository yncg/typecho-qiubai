# X-plore WiFi 文件共享 · HTTP 接口参考

X-plore（Android 文件管理器，lonelycatgames 出品）内置「WiFi 文件共享」，
开启后会起一个 HTTP 服务，手机浏览器打开就是一个网页版文件管理器。
本文档记录**网页版实际使用的 HTTP 接口**，因此可以在没有浏览器的情况下脚本化操作手机文件。

## 1. 识别是不是这个服务

随便请求一个路径，返回 HTML 里带这些特征即为此服务：

```
<title>X-plore File Manager</title>
<script src="main.js">
<span id="title">X-plore WiFi file manager</span>
```

- 网页 UI 资源：`/` 返回 ~10KB 的 HTML，另有 `/main.js`（约 110KB）、`/g.js`、`/main.css`
- 接口逻辑全在 `main.js` 里，搜索 `cmd=` 即可看到全部命令
- 响应头特征：`Content-Type: text/html`（网页）、`application/json`（接口）

## 2. 通用约定

| 项目 | 说明 |
|---|---|
| 服务地址 | `http://<手机IP>:<端口>`，常见 `1111`、`8080` |
| 路径写法 | **绝对路径直接当 URL 路径**，逐段 `encodeURIComponent`（`/` 保留） |
| 示例 | `/storage/emulated/0/htdocs/admin/menu.php` |
| 命令参数 | 统一用查询串 `?cmd=<命令>` |
| 密码 | 若共享设了密码，所有请求追加 `&pass=<hash>`；hash 算法见第 5 节 |
| 多文件系统 | 部分条目需要追加 `&fs=<n>`，内部存储一般不需要 |
| 公共响应字段 | `hasDon`（是否捐赠版）几乎每个响应都带，可忽略 |

**存储根**（`cmd=list_root` 返回）：

```
/storage/emulated/0  内部存储（用户最常放网站文件的 htdocs 就在这下面）
/                    根（需要 X-plore 有 root 或 all-files 权限）
```

## 3. 命令清单

标注：✅ = 已在真机（小米，Android）上实测通过；⚠️ = 来自官方前端源码，本机未及验证；❌ = 本机服务器不支持。

### 3.1 读取

| 命令 | 方法 | 请求 | 响应 |
|---|---|---|---|
| 列存储根 | GET | `/?cmd=list_root&filter=dirs` | ✅ `{"files":[{"mount":"/storage/emulated/0","label":"内部存储设备","space_total":N,"space_free":N}],"device_name":"...","device_uuid":N}` |
| 列目录 | GET | `<路径>?cmd=list` | ✅ `{"files":[{"n":"名字","t":1或2,"size":N,"has_children":true}]}` |
| 只列目录 | GET | `<路径>?cmd=list&filter=dirs` | ✅ 同上，`t=1` 是目录、`t=2` 是文件 |
| 下载文件 | GET | `<路径>?cmd=file` | ✅ 原始字节流 |
| 读文本 | GET | `<路径>?cmd=text_file` | ⚠️ 返回文本内容 |
| 是否存在 | GET | `<路径>?cmd=exists` | ✅ `{"exists":true}` / `{"exists":false}` |
| 大小 | GET | `<路径>?cmd=dir_size` | ✅ `{"size":123}`，目录会递归统计 |
| 缩略图 | GET | `<路径>?cmd=thumbnail` | ⚠️ 返回图片缩略图 |
| 内置图标 | GET | `/<iconId>?cmd=res_id` | ⚠️ 返回图标 |
| 扩展名图标 | GET | `/<扩展名>?cmd=ext_icon` | ⚠️ 返回图标 |
| 语言包 | GET | `/loc.json?cmd=localize` | ⚠️ 返回界面文案 JSON |

### 3.2 写入

| 命令 | 方法 | 请求 | 响应 |
|---|---|---|---|
| **上传 / 覆盖** | POST | `<路径>?cmd=file&size=<字节数>&file_date=<毫秒时间戳>`，**请求体 = 文件原始内容** | ✅ 成功 `{"length":N,"vol_free_space":N}`；失败 `{"ok":false}` |
| **删除** | DELETE | `<路径>?cmd=delete` | ✅ 成功 `{"ok":true}`；文件不存在 `{"ok":false}` |
| 重命名 / 移动 | GET | `<源路径>?cmd=rename&n=<URL编码的目标完整路径>` | ⚠️ `{"ok":true}` |
| 新建目录 | GET | `<新路径>?cmd=new_dir` | ❌ 本机返回 `Invalid command: new_dir` |
| 隐藏 / 取消隐藏 | GET | `<路径>?cmd=hide_unhide[&hide]` | ❌ 本机返回 `Invalid command: hide_unhide` |
| 打包下载 | GET | `<目录>/<名字>.zip?cmd=zip&...` | ⚠️ 最后的 `.zip` 名是假的，只用于浏览器下载时命名 |
| 退出共享 | GET | `/?cmd=quit` | ⚠️ `{"ok":true}`，会让手机端关闭服务 |

### 3.3 未知命令的报错

不支持的命令返回 HTTP 500 + 纯文本：

```
Server internal error: Invalid command: new_dir
```

可用这招**探测服务器支持哪些命令**。

## 4. 上传的关键细节（最容易踩坑）

上传接口来自前端 `UploadTask`，等价于：

```js
xhr.open("POST", encodeURI(path) + "?cmd=file&size=" + file.size
                       + "&file_date=" + file.lastModified);
xhr.send(file);          // 请求体是原始文件内容
```

必须注意：

1. **`size` 必须与请求体字节数完全一致**。差 1 个字节服务器就返回 `{"ok":false}`，且不创建文件。
   本机踩坑实录：`hello-xplore` 实际 12 字节却传了 `size=11`，一直失败。
2. `file_date` 是**毫秒**（前端传的是 `File.lastModified`，毫秒）。
3. 该命令会**覆盖已存在的文件**，不需要先删除。
4. 上传成功后目标目录里会立刻出现该文件，可用 `cmd=list` 或 `cmd=file` 回读校验。
5. Content-Type 不被校验，`application/octet-stream`、默认的表单类型都能过。
6. 只能写**文件**，写不了目录（没有 mkdir）。

## 5. 密码参数 `pass` 的算法

若手机端设置了共享密码，前端用 `md5.js` 算出 6 字节哈希（前端 `setPassword` 函数）：

```js
m = md5(password);                 // 4 个 32 位小端整数
b(i) = (m[i>>2] >>> ((i&3)*8)) & 0xff;
t = [b0^b6, b1^b7, b2^b8, b3^b9, b4^b10, b5^b11];
passHash = hex(t);                 // 12 位十六进制
```

等价实现（Python）：

```python
import hashlib
d = hashlib.md5(password.encode()).digest()
pass_hash = bytes(d[i] ^ d[i + 6] for i in range(6)).hex()
```

请求时追加 `&pass=<passHash>`。没设密码就不用带（本机未设密码，未实测该分支）。

## 6. 使用注意

- **服务会随手机状态中断**：X-plore 切到后台、息屏、或被系统杀进程后，端口会直接拒连
  （`Connection refused`），此时 ping 手机是通的。需要重新打开 X-plore 的 WiFi 共享。
- **服务偶发抖动**：首次请求或连续请求时可能出现超时，脚本里做退避重试即可（本工具默认重试 4 次）。
- **不要在别人网络里裸奔**：这个服务没有加密，拿到 IP 就能读写手机文件。用完尽快关掉。
- **权限**：新版本 Android 上 X-plore 需要「所有文件访问权限」，否则写 `/storage/emulated/0` 下某些目录会失败。
