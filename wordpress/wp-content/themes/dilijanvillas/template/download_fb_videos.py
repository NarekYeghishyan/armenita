"""
Скачивание видео Facebook по URL в папку website/videos/ (рядом со скриптом).

Примеры:
  python download_fb_videos.py
       (если рядом со скриптом есть info.json с facebook_video_urls — подхватит сам)
  python download_fb_videos.py "https://www.facebook.com/reel/123456"
  python download_fb_videos.py --json info.json
  python download_fb_videos.py -o ./website/videos URL1 URL2

В info.json ищутся поля: facebook_video_urls, video_urls или fb_video_urls (массив строк).

Для части роликов нужен вход в Facebook: экспортируйте cookies в Netscape-файл и укажите --cookies cookies.txt
или установите yt-dlp с поддержкой браузера: pip install "yt-dlp[default,curl-cffi]"
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

import yt_dlp


def _ensure_utf8_stdio() -> None:
    for stream in (sys.stdout, sys.stderr):
        reconf = getattr(stream, "reconfigure", None)
        if callable(reconf):
            try:
                reconf(encoding="utf-8", errors="replace")
            except Exception:
                pass


def load_json_loose(path: Path) -> dict:
    text = path.read_text(encoding="utf-8")
    text = re.sub(r",(\s*[}\]])", r"\1", text)
    return json.loads(text)


def load_urls_from_json(path: Path) -> list[str]:
    data = load_json_loose(path)
    for key in ("facebook_video_urls", "video_urls", "fb_video_urls"):
        v = data.get(key)
        if isinstance(v, list):
            return [str(x).strip() for x in v if str(x).strip()]
    return []


def default_videos_dir() -> Path:
    return Path(__file__).resolve().parent / "website" / "videos"


def default_info_json_path() -> Path:
    """info.json в той же папке, что и скрипт."""
    return Path(__file__).resolve().parent / "info.json"


def build_ydl_opts(out_dir: Path, cookies: Path | None) -> dict:
    out_dir.mkdir(parents=True, exist_ok=True)
    opts: dict = {
        "outtmpl": str(out_dir / "%(id)s.%(ext)s"),
        # Одно файл без склейки через ffmpeg (если нужен лучший mux — поставьте ffmpeg)
        "format": "best",
        "noplaylist": True,
        "ignoreerrors": False,
        "restrictfilenames": True,
        "retries": 3,
        "fragment_retries": 3,
    }
    if cookies and cookies.is_file():
        opts["cookiefile"] = str(cookies)
    return opts


def main() -> int:
    _ensure_utf8_stdio()
    ap = argparse.ArgumentParser(description="Скачать видео Facebook по URL")
    ap.add_argument("urls", nargs="*", help="URL видео / Reels / watch")
    ap.add_argument(
        "--json",
        dest="json_path",
        type=Path,
        help="JSON (например info.json) с массивом URL в facebook_video_urls",
    )
    ap.add_argument(
        "-o",
        "--output",
        type=Path,
        default=None,
        help=f"Папка сохранения (по умолчанию: {default_videos_dir()})",
    )
    ap.add_argument(
        "--cookies",
        type=Path,
        default=None,
        help="Файл cookies в формате Netscape (для приватных/ограниченных видео)",
    )
    args = ap.parse_args()

    out_dir = args.output if args.output is not None else default_videos_dir()

    urls: list[str] = [u.strip() for u in args.urls if u.strip()]

    json_file: Path | None = None
    if args.json_path is not None:
        json_file = args.json_path
    elif not urls:
        # Без URL и без --json: пробуем info.json рядом со скриптом
        candidate = default_info_json_path()
        if candidate.is_file():
            json_file = candidate

    if json_file is not None:
        if not json_file.is_file():
            print(f"Файл не найден: {json_file}", file=sys.stderr)
            return 1
        urls.extend(load_urls_from_json(json_file))

    if not urls:
        print(
            "Нет URL: задайте ссылки в командной строке, "
            "или добавьте facebook_video_urls в info.json рядом со скриптом, "
            "или укажите файл:  --json путь\\к\\info.json",
            file=sys.stderr,
        )
        return 1

    ydl_opts = build_ydl_opts(out_dir, args.cookies)

    failed = 0
    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        for url in urls:
            print(f"Скачивание: {url}")
            try:
                ydl.download([url])
            except yt_dlp.utils.DownloadError as e:
                print(f"Ошибка: {url}\n{e}", file=sys.stderr)
                failed += 1
            except Exception as e:
                print(f"Ошибка: {url}\n{e}", file=sys.stderr)
                failed += 1

    print(f"Готово. Файлы в: {out_dir.resolve()}")
    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main())
