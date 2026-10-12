#!/usr/bin/env python3
"""模拟 X-plore WiFi 文件共享的 HTTP 接口，用于离线测试 xplore.py

只实现真机上实测确认过的行为，包括：
  - 上传时 size 与实际字节数不符就返回 {"ok":false}（真机就是这个脾气）
  - 不支持的命令返回 HTTP 500 + "Server internal error: Invalid command: xxx"
"""
import json
import os
import sys
import urllib.parse
from http.server import BaseHTTPRequestHandler, HTTPServer

ROOT = sys.argv[1] if len(sys.argv) > 1 else "/tmp/mockphone"
PORT = int(sys.argv[2]) if len(sys.argv) > 2 else 18811
SUPPORTED = {"list", "list_root", "file", "delete", "exists", "dir_size",
             "rename", "text_file", "quit", "localize"}


def local(path):
    p = urllib.parse.unquote(path)
    return os.path.normpath(os.path.join(ROOT, p.lstrip("/")))


def j(obj, code=200):
    return code, json.dumps(obj).encode(), "application/json"


def do_list(p, dirs_only=False):
    if not os.path.isdir(p):
        return j({"files": [], "empty": True, "hasDon": True})
    out = []
    for n in sorted(os.listdir(p)):
        full = os.path.join(p, n)
        isdir = os.path.isdir(full)
        if dirs_only and not isdir:
            continue
        item = {"n": n, "t": 1 if isdir else 2, "hasDon": True}
        if isdir:
            item["has_children"] = True
        else:
            item["size"] = os.path.getsize(full)
        out.append(item)
    return j({"files": out, "hasDon": True})


class H(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"

    def log_message(self, *a):
        pass

    def _send(self, code, body, ctype):
        self.send_response(code)
        self.send_header("Content-Type", ctype)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def _route(self, method):
        u = urllib.parse.urlparse(self.path)
        q = urllib.parse.parse_qs(u.query, keep_blank_values=True)
        cmd = (q.get("cmd") or [""])[0]
        path = urllib.parse.unquote(u.path)
        p = local(path)

        if cmd and cmd not in SUPPORTED:
            self._send(500, f"Server internal error: Invalid command: {cmd}".encode(),
                       "text/plain")
            return

        # ---- 读取 ----
        if cmd == "list_root":
            body = {"files": [{"mount": ROOT, "label": "内部存储设备",
                               "space_total": 1000000000, "space_free": 500000000,
                               "has_children": True, "t": 0}],
                    "device_name": "MockDevice", "device_uuid": 1, "hasDon": True}
            self._send(200, json.dumps(body).encode(), "application/json")
            return
        if cmd == "list":
            if u.path in ("", "/") and os.path.isdir(ROOT):
                p = ROOT
            code, body, ct = do_list(p, dirs_only="filter" in q and q["filter"][0] == "dirs")
            self._send(code, body, ct)
            return
        if cmd == "exists":
            self._send(200, json.dumps({"exists": os.path.exists(p), "hasDon": True}).encode(),
                       "application/json")
            return
        if cmd == "dir_size":
            total = 0
            if os.path.isfile(p):
                total = os.path.getsize(p)
            elif os.path.isdir(p):
                for dp, _dn, fns in os.walk(p):
                    for f in fns:
                        total += os.path.getsize(os.path.join(dp, f))
            self._send(200, json.dumps({"size": total, "hasDon": True}).encode(),
                       "application/json")
            return
        if cmd == "text_file":
            if not os.path.isfile(p):
                self._send(404, b"File not found", "text/plain")
                return
            self._send(200, open(p, "rb").read(), "text/plain; charset=utf-8")
            return
        if cmd == "rename":
            dst = urllib.parse.unquote((q.get("n") or [""])[0])
            dp = local(dst)
            if not os.path.exists(p):
                self._send(200, json.dumps({"ok": False, "hasDon": True}).encode(),
                           "application/json")
                return
            os.makedirs(os.path.dirname(dp), exist_ok=True)
            os.replace(p, dp)
            self._send(200, json.dumps({"ok": True, "hasDon": True}).encode(),
                       "application/json")
            return
        if cmd == "quit":
            self._send(200, json.dumps({"ok": True, "hasDon": True}).encode(),
                       "application/json")
            return

        # ---- 下载 / 上传 走同一个 cmd=file ----
        if cmd == "file":
            if method == "POST":
                size = int((q.get("size") or ["0"])[0])
                data = self.rfile.read(int(self.headers.get("Content-Length", 0)))
                # 真机行为：size 必须与请求体字节数完全一致
                if size != len(data):
                    self._send(200, json.dumps({"ok": False, "hasDon": True}).encode(),
                               "application/json")
                    return
                if not os.path.isdir(os.path.dirname(p)):
                    self._send(200, json.dumps({"ok": False, "hasDon": True}).encode(),
                               "application/json")
                    return
                open(p, "wb").write(data)
                if q.get("file_date"):
                    os.utime(p, (os.path.getmtime(p), int(q["file_date"][0]) / 1000))
                self._send(200, json.dumps({"length": len(data), "vol_free_space": 500000000,
                                            "hasDon": True}).encode(), "application/json")
                return
            if not os.path.isfile(p):
                self._send(404, b"File not found", "text/plain")
                return
            self._send(200, open(p, "rb").read(), "application/octet-stream")
            return

        # ---- 删除 ----
        if cmd == "delete" and method == "DELETE":
            ok = False
            if os.path.isfile(p):
                os.remove(p); ok = True
            elif os.path.isdir(p):
                import shutil; shutil.rmtree(p); ok = True
            self._send(200, json.dumps({"ok": ok, "vol_free_space": 500000000,
                                        "hasDon": True}).encode(), "application/json")
            return

        self._send(404, b"File not found: " + path.encode(), "text/plain")

    def do_GET(self):
        self._route("GET")

    def do_POST(self):
        self._route("POST")

    def do_DELETE(self):
        self._route("DELETE")


if __name__ == "__main__":
    os.makedirs(ROOT, exist_ok=True)
    print(f"mock X-plore 已启动: http://127.0.0.1:{PORT}/  根目录 {ROOT}", flush=True)
    HTTPServer(("127.0.0.1", PORT), H).serve_forever()
