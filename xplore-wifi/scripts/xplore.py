#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
xplore.py —— X-plore WiFi 文件管理器（Android）命令行客户端

把 X-plore 的「局域网 WiFi 文件共享」HTTP 接口封装成可脚本化调用的工具，
支持列出 / 读取 / 上传 / 下载 / 删除 / 重命名 / 判断存在 / 目录大小 / 批量同步。

服务默认地址 http://192.168.0.111:1111 ，可用 --host 或环境变量 XPLORE_HOST 覆盖。

用法示例：
    xplore.py roots                          # 列出手机存储根（拿到挂载点）
    xplore.py ls /storage/emulated/0/htdocs  # 列目录
    xplore.py cat /storage/emulated/0/a.txt  # 读文本
    xplore.py get <远端路径> [-o 本地路径]     # 下载
    xplore.py put <本地文件> <远端路径>        # 上传（自动校验）
    xplore.py rm <远端路径> [<远端路径> ...]   # 删除
    xplore.py mv <远端源> <远端目标>           # 重命名 / 移动
    xplore.py exists <远端路径>               # 是否存在
    xplore.py size <远端路径>                 # 大小（目录会递归统计）
    xplore.py sync <本地目录> <远端目录> [--only a.php,b.php] [--prune]
"""
from __future__ import annotations

import argparse
import hashlib
import json
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

DEFAULT_HOST = os.environ.get("XPLORE_HOST", "192.168.0.111:1111")
USER_AGENT = "xplore-cli/1.0"


class XploreError(RuntimeError):
    pass


def _md5_hex(b: bytes) -> str:
    return hashlib.md5(b).hexdigest()


def pass_hash(password: str) -> str:
    """按 X-plore 前端的算法由明文密码算出 pass 参数。

    前端把 md5 结果当作 4 个 32 位小端整数取字节，再异或成 6 字节。
    等价于：digest[i] ^ digest[i + 6]（i = 0..5），取十六进制。
    """
    d = hashlib.md5(password.encode("utf-8")).digest()
    return bytes(d[i] ^ d[i + 6] for i in range(6)).hex()


class Xplore:
    def __init__(self, host: str = DEFAULT_HOST, password: str | None = None,
                 timeout: float = 45.0, retries: int = 4, verbose: bool = False,
                 scheme: str = "http"):
        host = host.strip()
        if host.startswith("http://") or host.startswith("https://"):
            scheme, host = host.split("://", 1)
        self.base = f"{scheme}://{host.rstrip('/')}"
        self.password = password or os.environ.get("XPLORE_PASS")
        self.pwhash = pass_hash(self.password) if self.password else None
        self.timeout = timeout
        self.retries = retries
        self.verbose = verbose

    # ---------- 底层 ----------

    @staticmethod
    def enc_path(path: str) -> str:
        """逐段 URL 编码（与前端 g.urlEncode 一致），保留开头的 /"""
        segs = [urllib.parse.quote(s, safe="") for s in path.split("/") if s != ""]
        return "/" + "/".join(segs)

    def _url(self, path: str, query: str = "") -> str:
        # 根路径查询（cmd=list_root 等）用 "/" 即可
        url = self.base + (self.enc_path(path) if path not in ("", "/") else "/")
        if query:
            url += "?" + query
        return url

    def _request(self, path: str, query: str = "", method: str = "GET",
                 data: bytes | None = None, raw: bool = False):
        if self.pwhash:
            query = (query + "&" if query else "") + "pass=" + self.pwhash
        url = self._url(path, query)
        last = None
        for attempt in range(self.retries):
            try:
                req = urllib.request.Request(url, data=data, method=method)
                req.add_header("User-Agent", USER_AGENT)
                if data is not None:
                    req.add_header("Content-Type", "application/octet-stream")
                with urllib.request.urlopen(req, timeout=self.timeout) as r:
                    body = r.read()
                if self.verbose:
                    print(f"    [{method}] {url}  -> {len(body)} 字节", file=sys.stderr)
                return body if raw else body.decode("utf-8", "replace")
            except urllib.error.HTTPError as e:
                # 4xx/5xx：服务器有明确回应，不再重试（500 表示命令不支持）
                txt = e.read().decode("utf-8", "replace")
                raise XploreError(f"HTTP {e.code}: {txt.strip()[:200]}") from None
            except Exception as e:                      # 超时 / 连接被拒 / 复位
                last = e
                if attempt < self.retries - 1:
                    time.sleep(1.0 + attempt * 1.5)     # 这台服务偶发抖动，退避重试
        raise XploreError(
            f"无法连接 {self.base}（已重试 {self.retries} 次）：{last}\n"
            "  请确认手机上 X-plore 的「WiFi 文件共享」处于开启状态、且屏幕未锁定。")

    @staticmethod
    def _json(txt: str) -> dict:
        try:
            return json.loads(txt)
        except Exception:
            raise XploreError(f"返回不是合法 JSON：{txt[:200]}") from None

    # ---------- 读操作 ----------

    def roots(self) -> dict:
        """列出存储根：/storage/emulated/0 是内部存储，/ 是根。"""
        return self._json(self._request("/", "cmd=list_root&filter=dirs"))

    def ls(self, path: str, dirs_only: bool = False) -> list[dict]:
        q = "cmd=list" + ("&filter=dirs" if dirs_only else "")
        return self._json(self._request(path, q)).get("files", [])

    def walk(self, path: str):
        """递归遍历，产出 (相对路径, 是否目录, 大小)"""
        def rec(prefix: str):
            for f in self.ls(prefix or path):
                name = str(f.get("n"))
                full = (prefix or path).rstrip("/") + "/" + name
                isdir = f.get("t") == 1
                yield full, isdir, f.get("size")
                if isdir:
                    yield from rec(full)
        yield from rec(path.rstrip("/"))

    def cat(self, path: str) -> str:
        return self._request(path, "cmd=text_file")

    def download(self, path: str) -> bytes:
        return self._request(path, "cmd=file", raw=True)

    def exists(self, path: str) -> bool:
        return bool(self._json(self._request(path, "cmd=exists")).get("exists"))

    def dir_size(self, path: str) -> int:
        return int(self._json(self._request(path, "cmd=dir_size")).get("size", 0))

    def stat(self, path: str) -> dict:
        """取名称/类型/大小，一次 list 父目录即可"""
        parent, _, name = path.rstrip("/").rpartition("/")
        parent = parent or "/"
        for f in self.ls(parent):
            if str(f.get("n")) == name:
                return {"name": name, "is_dir": f.get("t") == 1,
                        "size": f.get("size"), "path": path}
        return {}

    # ---------- 写操作 ----------

    def upload(self, local: str, remote: str, verify: bool = True,
               chunk: int | None = None) -> dict:
        """上传单个文件。size 必须与请求体字节数完全一致，否则服务器返回 ok:false。"""
        data = open(local, "rb").read()
        mtime_ms = int(os.path.getmtime(local) * 1000)
        q = f"cmd=file&size={len(data)}&file_date={mtime_ms}"
        txt = self._request(remote, q, method="POST", data=data)
        res = self._json(txt)
        if res.get("ok") is False or "length" not in res:
            raise XploreError(f"上传被拒绝：{txt.strip()[:200]}")
        if verify:
            back = self.download(remote)
            if _md5_hex(back) != _md5_hex(data):
                raise XploreError(f"上传后校验不一致：{remote}"
                                  f"（本地 {len(data)} 字节 / 远端 {len(back)} 字节）")
        return res

    def delete(self, remote: str) -> bool:
        return bool(self._json(
            self._request(remote, "cmd=delete", method="DELETE")).get("ok"))

    def rename(self, src: str, dst: str) -> bool:
        """重命名或移动（目标写完整路径）"""
        q = "cmd=rename&n=" + urllib.parse.quote(dst, safe="")
        return bool(self._json(self._request(src, q)).get("ok"))

    def quit(self) -> bool:
        """让手机端退出 WiFi 共享（前端“退出”按钮走的就是这个）"""
        return bool(self._json(self._request("/", "cmd=quit")).get("ok"))


# ---------------------------------------------------------------------------
# 命令行
# ---------------------------------------------------------------------------

def human(n) -> str:
    if n is None:
        return "-"
    n = float(n)
    for u in ("B", "KB", "MB", "GB", "TB"):
        if n < 900 or u == "TB":
            return f"{n:.0f}{u}" if u == "B" else f"{n:.1f}{u}"
        n /= 1024
    return f"{n:.1f}TB"


def cmd_roots(xp: Xplore, a):
    r = xp.roots()
    print(f"设备: {r.get('device_name')}   免费空间: {human(r.get('space_free'))}")
    for f in r.get("files", []):
        print(f"  {f.get('mount'):<26} {f.get('label')}   "
              f"总 {human(f.get('space_total'))} / 剩 {human(f.get('space_free'))}")


def cmd_ls(xp: Xplore, a):
    for f in xp.ls(a.path, a.dirs_only):
        kind = "DIR " if f.get("t") == 1 else "FILE"
        print(f"  {kind} {str(f.get('n')):<44} {human(f.get('size'))}")


def cmd_tree(xp: Xplore, a):
    base = a.path.rstrip("/")
    for full, isdir, size in xp.walk(a.path):
        rel = full[len(base):].lstrip("/")
        print(("  [D] " if isdir else "  [F] ") + rel + ("" if isdir else f"  {human(size)}"))


def cmd_cat(xp: Xplore, a):
    print(xp.cat(a.path), end="")


def cmd_get(xp: Xplore, a):
    data = xp.download(a.path)
    out = a.output or os.path.basename(a.path.rstrip("/"))
    open(out, "wb").write(data)
    print(f"已下载 {len(data)} 字节 -> {out}")


def cmd_put(xp: Xplore, a):
    res = xp.upload(a.local, a.remote, verify=not a.no_verify)
    print(f"✓ 已上传 {a.local} -> {a.remote}（{res.get('length')} 字节，已校验）")


def cmd_rm(xp: Xplore, a):
    for p in a.paths:
        ok = xp.delete(p)
        print(f"  {'✓ 已删除' if ok else '✗ 删除失败（文件可能不存在）'}  {p}")


def cmd_mv(xp: Xplore, a):
    ok = xp.rename(a.src, a.dst)
    print(f"  {'✓' if ok else '✗'} {a.src}  ->  {a.dst}")


def cmd_exists(xp: Xplore, a):
    for p in a.paths:
        print(f"  {'存在' if xp.exists(p) else '不存在'}  {p}")


def cmd_size(xp: Xplore, a):
    n = xp.dir_size(a.path)
    print(f"  {human(n)}  ({n} 字节)  {a.path}")


def cmd_sync(xp: Xplore, a):
    """把本地目录里的文件同步到远端目录；默认只传大小不同的，--force 全传。"""
    lroot = os.path.abspath(a.local_dir)
    rroot = a.remote_dir.rstrip("/")
    only = set(x.strip() for x in a.only.split(",")) if a.only else None

    wanted = []
    for dirpath, _dirnames, filenames in os.walk(lroot):
        for fn in filenames:
            if fn in (".DS_Store", "Thumbs.db"):
                continue
            lp = os.path.join(dirpath, fn)
            rel = os.path.relpath(lp, lroot).replace(os.sep, "/")
            if only and rel not in only:
                continue
            wanted.append((lp, rel))

    print(f"本地 {lroot}  ->  远端 {rroot}")
    print(f"待处理 {len(wanted)} 个文件\n")

    remote_index = {}
    try:
        for full, isdir, size in xp.walk(rroot):
            if not isdir:
                remote_index[full[len(rroot):].lstrip("/")] = size
    except XploreError as e:
        print(f"（无法读取远端目录列表，将全部上传）{e}")

    up = same = 0
    for lp, rel in sorted(wanted, key=lambda x: x[1]):
        size = os.path.getsize(lp)
        remote = f"{rroot}/{rel}"
        if not a.force and remote_index.get(rel) == size:
            print(f"  跳过（大小相同）  {rel}")
            same += 1
            continue
        xp.upload(lp, remote, verify=not a.no_verify)
        print(f"  ✓ 已同步  {rel:<42} {human(size)}")
        up += 1
        time.sleep(0.2)

    print(f"\n上传 {up} 个，跳过 {same} 个")

    if a.prune:
        keep = {rel for _lp, rel in wanted}
        extra = sorted(set(remote_index) - keep)
        if extra:
            print(f"\n--prune：远端有 {len(extra)} 个本地没有的文件待删除")
            for rel in extra:
                if a.prune_yes or input(f"  删除 {rroot}/{rel} ? [y/N] ").lower() == "y":
                    xp.delete(f"{rroot}/{rel}")
                    print(f"  已删除 {rel}")
        else:
            print("\n--prune：远端没有多余文件")


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        prog="xplore.py",
        description="X-plore WiFi 文件管理器命令行客户端",
        formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--host", default=DEFAULT_HOST,
                   help=f"服务地址 host:port（默认 {DEFAULT_HOST}，或环境变量 XPLORE_HOST）")
    p.add_argument("--passw", dest="password", default=None,
                   help="共享密码（如设置了，或环境变量 XPLORE_PASS）")
    p.add_argument("--timeout", type=float, default=45.0, help="单次请求超时秒数")
    p.add_argument("--retries", type=int, default=4, help="失败重试次数")
    p.add_argument("-v", "--verbose", action="store_true", help="打印每个请求")
    sub = p.add_subparsers(dest="cmd", required=True)

    s = sub.add_parser("roots", help="列出存储根")
    s.set_defaults(fn=cmd_roots)

    s = sub.add_parser("ls", help="列目录")
    s.add_argument("path")
    s.add_argument("-d", "--dirs-only", action="store_true", help="只看目录")
    s.set_defaults(fn=cmd_ls)

    s = sub.add_parser("tree", help="递归列目录")
    s.add_argument("path")
    s.set_defaults(fn=cmd_tree)

    s = sub.add_parser("cat", help="读取文本文件")
    s.add_argument("path")
    s.set_defaults(fn=cmd_cat)

    s = sub.add_parser("get", help="下载文件")
    s.add_argument("path")
    s.add_argument("-o", "--output")
    s.set_defaults(fn=cmd_get)

    s = sub.add_parser("put", help="上传文件")
    s.add_argument("local")
    s.add_argument("remote")
    s.add_argument("--no-verify", action="store_true", help="跳过上传后的回读校验")
    s.set_defaults(fn=cmd_put)

    s = sub.add_parser("rm", help="删除")
    s.add_argument("paths", nargs="+")
    s.set_defaults(fn=cmd_rm)

    s = sub.add_parser("mv", help="重命名 / 移动")
    s.add_argument("src")
    s.add_argument("dst")
    s.set_defaults(fn=cmd_mv)

    s = sub.add_parser("exists", help="判断是否存在")
    s.add_argument("paths", nargs="+")
    s.set_defaults(fn=cmd_exists)

    s = sub.add_parser("size", help="文件/目录大小")
    s.add_argument("path")
    s.set_defaults(fn=cmd_size)

    s = sub.add_parser("sync", help="把本地目录同步到远端目录")
    s.add_argument("local_dir")
    s.add_argument("remote_dir")
    s.add_argument("--only", help="只同步这些相对路径，逗号分隔")
    s.add_argument("--force", action="store_true", help="大小相同也重传")
    s.add_argument("--no-verify", action="store_true", help="跳过上传后的回读校验")
    s.add_argument("--prune", action="store_true", help="删除远端多余的文件")
    s.add_argument("--prune-yes", action="store_true", help="配合 --prune，不再逐个确认")
    s.set_defaults(fn=cmd_sync)

    return p


def main(argv=None) -> int:
    a = build_parser().parse_args(argv)
    xp = Xplore(host=a.host, password=a.password, timeout=a.timeout,
                retries=a.retries, verbose=a.verbose)
    try:
        a.fn(xp, a)
        return 0
    except XploreError as e:
        print(f"错误：{e}", file=sys.stderr)
        return 1
    except KeyboardInterrupt:
        print("\n已中断", file=sys.stderr)
        return 130


if __name__ == "__main__":
    sys.exit(main())
