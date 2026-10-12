---
name: xplore-wifi
description: 通过手机上的 X-plore WiFi 文件共享（Android 文件管理器的局域网 HTTP 服务，网页标题为 "X-plore File Manager"）远程读写手机上任意文件。用于把本地文件同步/上传到手机、下载手机上的文件、列出手机目录、删除或重命名手机文件等。当用户给出类似 http://192.168.x.x:端口 的手机文件共享地址，或提到 X-plore、WiFi 文件共享、手机 htdocs、KSWEB 站点同步时使用。
agent_created: true
---

# X-plore WiFi 文件共享

## 用途

Android 上的 X-plore 文件管理器有个「WiFi 文件共享」功能：开启后手机起一个 HTTP 服务，
浏览器打开就是网页版文件管理器。这个 skill 封装了它背后的 HTTP 接口，
**不依赖浏览器**就能列目录、读文件、下载、上传、删除、重命名手机上的文件。

典型场景：把本地改好的网站文件同步到手机 KSWEB 的 `htdocs`、备份手机上的照片到电脑、
在手机上批量改名或清理文件。

## 何时使用

- 用户给了 `http://192.168.x.x:<端口>` 这样的地址，说是手机上共享的文件/网站
- 用户提到「X-plore」「WiFi 文件共享」「手机上的 htdocs」「同步到手机」
- 需要往手机写文件、或从手机取文件，且没有其他连接方式（无 adb、无网盘）

先确认目标确实是这个服务：请求根路径，返回的 HTML 里含 `<title>X-plore File Manager</title>`。
若是别的服务（CHFS、KSWEB 自带文件管理器等），接口完全不同，不要套用本 skill。

## 快速开始

脚本：`scripts/xplore.py`（纯标准库，Python 3.8+）

```bash
# 服务地址可用 --host 或环境变量 XPLORE_HOST 指定（默认 192.168.0.111:1111）
XPLORE_HOST=192.168.0.111:1111 python3 scripts/xplore.py roots
```

常用命令：

```bash
python3 scripts/xplore.py roots                          # 列出存储根，拿到挂载点
python3 scripts/xplore.py ls /storage/emulated/0/htdocs  # 列目录
python3 scripts/xplore.py ls <路径> -d                    # 只看目录
python3 scripts/xplore.py tree <路径>                     # 递归列目录
python3 scripts/xplore.py cat <路径>                      # 读文本
python3 scripts/xplore.py get <远端路径> -o 本地文件       # 下载
python3 scripts/xplore.py put <本地文件> <远端完整路径>     # 上传（带回读校验）
python3 scripts/xplore.py rm <远端路径> [<远端路径> ...]   # 删除
python3 scripts/xplore.py mv <远端源> <远端目标>           # 重命名 / 移动
python3 scripts/xplore.py exists <路径> ...                # 判断是否存在
python3 scripts/xplore.py size <路径>                      # 大小（目录递归统计）
python3 scripts/xplore.py sync <本地目录> <远端目录>        # 批量同步
```

`sync` 的附加选项：`--only a.php,b.php` 只同步指定相对路径、
`--force` 大小相同也重传、`--prune` 删除远端本地没有的文件（默认逐个确认，加 `--prune-yes` 免确认）。

## 关键约束（务必记住）

1. **上传时 `size` 必须与文件字节数完全一致**，差一个字节服务器就返回 `{"ok":false}` 且不创建文件。
   脚本已按文件实际长度自动填写，**自己手写请求时最容易在这里翻车**。
2. **只能写文件，不能建目录**。服务器不支持 `cmd=new_dir`（返回 `Invalid command`），
   上传前必须确保目标目录已存在，否则会失败。
3. **`file_date` 单位是毫秒**（前端传 `File.lastModified`）。
4. 删除用 **HTTP DELETE** 方法；对不存在的文件返回 `{"ok":false}`，不是错误。
5. 路径**逐段 URL 编码**后直接拼在 URL 上，注意中文/空格要编码。
6. 若共享设了密码，所有请求要带 `&pass=<hash>`（算法见 `references/api.md` 第 5 节），
   脚本用 `--passw` 或环境变量 `XPLORE_PASS` 传明文即可自动换算。

## 自测（没有真机时）

`scripts/mock_server.py` 在本地模拟同一套接口（含「size 不符就拒收」等真机脾气），
可在没有手机的情况下验证 `xplore.py` 的各项功能：

```bash
python3 scripts/mock_server.py /tmp/mockphone 18811 &
XPLORE_HOST=127.0.0.1:18811 python3 scripts/xplore.py ls /htdocs
```

## 排障

| 现象 | 原因与处理 |
|---|---|
| `Connection refused`（但 ping 手机通） | 手机端 X-plore 的 WiFi 共享已停（切后台/息屏/被杀）。让用户重新打开，注意保持前台 |
| 偶发超时后又正常 | 该服务有抖动，脚本已内置退避重试；必要时加大 `--timeout` / `--retries` |
| 上传返回 `{"ok":false}` | 十有八九是 `size` 与实际字节数不符；其次确认目标目录存在 |
| HTTP 500 `Invalid command: xxx` | 该服务器版本不支持这条命令，换等价做法 |
| 写入某些目录失败 | Android 11+ 需要 X-plore 拿到「所有文件访问权限」 |

## 安全提醒

这个服务**明文 HTTP、无认证**（除非设了密码），同一局域网内谁都能读写手机文件。
用完之后提示用户关闭共享；不要把地址发到不受信任的网络里。

## 参考

- `references/api.md` —— 完整接口参考：命令表、请求/响应示例、密码算法、
  以及「哪些命令在真机上实测通过、哪些待验证」。

## 本 skill 的来源与安全说明

本 skill 由本机对话中逆向 X-plore 网页版 `main.js` 得到接口并实测整理而成，无第三方代码。
脚本 `scripts/xplore.py` 仅使用 Python 标准库，行为限于：向用户指定的 HTTP 地址发起
`cmd=list/list_root/file/exists/dir_size/delete/rename/quit` 请求，
以及读写用户显式指定的本地文件路径。不访问网络其他目标，不执行外部命令，不做持久化写入。
