#!/usr/bin/env bash

# spusti pomocou: chmod +x update.sh && ./update.sh
set -euo pipefail

# ------- config -------
PROJECT_ROOT="${PROJECT_ROOT:-$(pwd)}"
QRCODE_DIR="$PROJECT_ROOT/extern/qrcode"
QRCODE_HPP="$QRCODE_DIR/qrcodegen.hpp"
QRCODE_CPP="$QRCODE_DIR/qrcodegen.cpp"
RAW_BASE="https://raw.githubusercontent.com/nayuki/QR-Code-generator/refs/heads/master/cpp"
INCONSOLATA_URL="https://github.com/googlefonts/Inconsolata/releases/download/v3.000/fonts_ttf.zip"
APT_PKGS=(
  build-essential gdb git curl wget unzip pkg-config ca-certificates
  fontconfig nlohmann-json3-dev
  libhpdf-dev libpng-dev zlib1g-dev liblzma-dev
  libmariadb-dev mariadb-client
  fonts-dejavu-core fonts-noto-core
)
# ----------------------

echo "==> Updating apt and installing base packages…"
export DEBIAN_FRONTEND=noninteractive
sudo apt-get update -y
sudo apt-get install -y --no-install-recommends "${APT_PKGS[@]}"

echo "==> Installing Inconsolata (TTF) from upstream…"
DEST="/usr/share/fonts/truetype/inconsolata"
if fc-list | grep -qi "inconsolata"; then
  echo "   - Inconsolata already present (fc-list). Skipping download."
else
  tmpdir="$(mktemp -d)"
  pushd "$tmpdir" >/dev/null
    if curl -fL -o fonts_ttf.zip "$INCONSOLATA_URL"; then
      unzip -q fonts_ttf.zip
      sudo mkdir -p "$DEST"
      mapfile -t ttf_list < <(find . -type f -iname "*.ttf" | sort)
      if [[ "${#ttf_list[@]}" -gt 0 ]]; then
        for f in "${ttf_list[@]}"; do
          sudo install -m 0644 -D "$f" "$DEST/$(basename "$f")"
        done
        echo "   - Installed TTFs to $DEST:"
        ls -1 "$DEST" | sed 's/^/     * /'
      else
        echo "   ! No .ttf files found in archive."
      fi
    else
      echo "   ! Download failed: $INCONSOLATA_URL"
    fi
  popd >/dev/null
  rm -rf "$tmpdir" || true
fi


echo "==> Refreshing font cache (fontconfig)…"
sudo fc-cache -f -v || true

echo "==> Verifying Inconsolata is available…"
if fc-list | grep -qi inconsolata; then
  echo "   - Inconsolata found in fontconfig."
else
  echo "   ! Inconsolata not found in fc-list (check install logs above)."
fi

echo "==> Removing old/conflicting Node packages (if any)…"
sudo apt-get remove -y --purge libnode-dev nodejs npm nodejs-doc || true
sudo apt-get autoremove -y || true
sudo rm -rf /usr/include/node || true

echo "==> Installing modern Node.js (LTS) via NodeSource…"
# Try 22.x first, fallback to 20.x if setup script fails
if curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -; then
  echo "   - NodeSource 22.x repo added."
else
  echo "   ! NodeSource 22.x failed, trying 20.x…"
  curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
fi
sudo apt-get install -y nodejs

echo "==> Node versions:"
echo "   - node: $(node -v 2>/dev/null || echo 'not found')"
echo "   - npm:  $(npm -v  2>/dev/null || echo 'not found')"

# Basic sanity check (need >= 14, prefer >= 18)
if ! command -v node >/dev/null; then
  echo "ERROR: node not installed properly."; exit 1
fi
NODE_MAJOR="$(node -v | sed 's/^v//' | cut -d. -f1)"
if [ "${NODE_MAJOR:-0}" -lt 14 ]; then
  echo "ERROR: Node.js is too old ($(node -v)). Expected >= 14."; exit 1
fi

echo "==> Installing bysquare CLI globally…"
sudo npm i -g bysquare@latest
echo "   - bysquare: $(command -v bysquare || echo 'not found')"

echo "==> Ensuring qrcodegen sources at $QRCODE_DIR"
mkdir -p "$QRCODE_DIR"
need_fetch=0
[[ ! -f "$QRCODE_HPP" ]] && need_fetch=1
[[ ! -f "$QRCODE_CPP" ]] && need_fetch=1
if [[ "$need_fetch" -eq 1 ]]; then
  echo "   - Downloading qrcodegen.hpp / qrcodegen.cpp (Nayuki)…"
  if curl -fsSL "$RAW_BASE/qrcodegen.hpp" -o "$QRCODE_HPP" \
     && curl -fsSL "$RAW_BASE/qrcodegen.cpp" -o "$QRCODE_CPP"; then
    echo "   - Downloaded via raw.githubusercontent.com"
  else
    echo "   ! Direct download failed. Falling back to sparse Git clone…"
    tmpdir2="$(mktemp -d)"
    git clone --depth 1 --filter=blob:none --sparse https://github.com/nayuki/QR-Code-generator.git "$tmpdir2/qr"
    git -C "$tmpdir2/qr" sparse-checkout set cpp
    cp "$tmpdir2/qr/cpp/qrcodegen."{hpp,cpp} "$QRCODE_DIR/"
    rm -rf "$tmpdir2"
    echo "   - Files copied from sparse clone."
  fi
else
  echo "   - Already present, skipping download."
fi

echo "==> Checking compiler…"
if command -v g++ >/dev/null 2>&1; then
  gxx_ver=$(g++ -dumpfullversion -dumpversion || true)
  echo "   - g++ version: ${gxx_ver}"
else
  echo "   ! g++ not found (unexpected). Installing build-essential again."
  sudo apt-get install -y build-essential
fi

# Voliteľný výpis všetkých truetype fontov
if [ -t 0 ]; then
  read -r -p "Show fonts? (y/N) " _ans
else
  _ans="n"
fi

case "${_ans}" in
  [yY]|[yY][eE][sS])
    echo "   (All truetype TTF/TTC discovered)"
    find /usr/share/fonts/truetype ~/.local/share/fonts ~/.fonts \
         -type f \( -iname '*.ttf' -o -iname '*.ttc' \) 2>/dev/null | sort | sed 's/^/   - /'
    ;;
  *)
    echo "   - Skipping font list."
    ;;
esac


echo
echo "Done. You can now build with your VS Code task (Ctrl+Shift+B)."
