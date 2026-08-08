#!/usr/bin/env python3
# Status line de Claude Code: muestra proyecto | carpeta actual | rama git.
import sys, os, json, subprocess

try:
    d = json.load(sys.stdin)
except Exception:
    d = {}

# Claude Code pasa el contexto por stdin. Tomamos la carpeta actual.
ws = d.get("workspace") or {}
cwd = ws.get("current_dir") or d.get("cwd") or os.getcwd()
base = os.path.basename(cwd.rstrip("/")) or cwd

# Rama de git (vacío si la carpeta no es un repo).
branch = ""
try:
    branch = subprocess.check_output(
        ["git", "-C", cwd, "rev-parse", "--abbrev-ref", "HEAD"],
        stderr=subprocess.DEVNULL,
    ).decode().strip()
except Exception:
    pass

PROJECT = "Wellness Circle Academy"

parts = [PROJECT, base]
if branch:
    parts.append("git:" + branch)
print("   ".join(parts))
