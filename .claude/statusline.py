#!/usr/bin/env python3
# Status line de Claude Code: PROYECTO (verde) | cuenta Claude | cuenta git | cuenta Railway | carpeta | rama git.
#
# Las cuentas se verifican EN VIVO contra el servidor de cada proveedor (nunca
# se lee un archivo de config cacheado como fuente de identidad — un
# .claude.json viejo puede mostrar una cuenta que ya no es la real). Con
# caché corta de 15s por sesión: Claude Code cancela el script si tarda
# mucho y llega un evento nuevo antes de que termine (doc oficial,
# code.claude.com/docs/en/statusline.md, sección "Script errors or hangs"),
# así que sin caché la barra puede quedarse en blanco/vieja indefinidamente
# durante una sesión activa. 15s de antigüedad máxima es el punto medio que
# Rub eligió entre "siempre en vivo" (se cancela y no se ve) y "fijo para
# siempre" (mentía si cambiabas de cuenta) — decisión tomada 2026-08-11.
import sys, os, json, subprocess, urllib.request, time, tempfile

GREEN = "\033[32m"
RESET = "\033[0m"

CACHE_TTL_SECONDS = 15

try:
    d = json.load(sys.stdin)
except Exception:
    d = {}

ws = d.get("workspace") or {}
cwd = ws.get("current_dir") or d.get("cwd") or os.getcwd()
base = os.path.basename(cwd.rstrip("/")) or cwd
session_id = d.get("session_id") or "nosession"

# Rama de git: rápida y local, sin caché, siempre en vivo.
branch = ""
try:
    branch = subprocess.check_output(
        ["git", "-C", cwd, "rev-parse", "--abbrev-ref", "HEAD"],
        stderr=subprocess.DEVNULL,
    ).decode().strip()
except Exception:
    pass


def fetch_claude_account():
    config_dir = os.environ.get("CLAUDE_CONFIG_DIR") or os.path.expanduser("~/.claude")
    with open(os.path.join(config_dir, ".credentials.json")) as f:
        creds = json.load(f)
    token = (creds.get("claudeAiOauth") or {}).get("accessToken") or ""
    if not token:
        return ""
    req = urllib.request.Request(
        "https://api.anthropic.com/api/oauth/profile",
        headers={
            "Authorization": "Bearer " + token,
            "anthropic-beta": "oauth-2025-04-20",
        },
    )
    with urllib.request.urlopen(req, timeout=10) as resp:
        profile = json.loads(resp.read().decode())
    return (profile.get("account") or {}).get("email") or ""


def fetch_railway_account():
    out = subprocess.check_output(
        ["railway", "whoami", "--json"], stderr=subprocess.DEVNULL, timeout=10,
    ).decode()
    return json.loads(out).get("email") or ""


# Cuenta de git: config local del repo, rápida, sin red — siempre en vivo, sin caché.
git_account = ""
try:
    git_account = subprocess.check_output(
        ["git", "-C", cwd, "config", "user.email"],
        stderr=subprocess.DEVNULL,
    ).decode().strip()
except Exception:
    pass

# Claude Code y Railway: verificación de red, cacheada 15s por sesión.
cache_path = os.path.join(
    tempfile.gettempdir(), f"statusline-accounts-{session_id}.json"
)
cached = None
try:
    if time.time() - os.path.getmtime(cache_path) < CACHE_TTL_SECONDS:
        with open(cache_path) as f:
            cached = json.load(f)
except Exception:
    cached = None

if cached is not None:
    claude_account = cached.get("claude_account", "")
    railway_account = cached.get("railway_account", "")
else:
    claude_account = ""
    try:
        claude_account = fetch_claude_account()
    except Exception:
        pass

    railway_account = ""
    try:
        railway_account = fetch_railway_account()
    except Exception:
        pass

    try:
        with open(cache_path, "w") as f:
            json.dump({"claude_account": claude_account, "railway_account": railway_account}, f)
    except Exception:
        pass

PROJECT = "Wellness Circle Academy".upper()

parts = [f"{GREEN}{PROJECT}{RESET}"]
if claude_account:
    parts.append("cc:" + claude_account)
if git_account:
    parts.append("gh:" + git_account)
if railway_account:
    parts.append("rw:" + railway_account)
parts.append(base)
if branch:
    parts.append("git:" + branch)
print("   ".join(parts))
