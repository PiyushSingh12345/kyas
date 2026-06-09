#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VENV_DIR="${APP_DIR}/.venv-budget-head-ocr"
OCR_HOME="${APP_DIR}/storage/app/budget-head-ocr-home"
OCR_MODEL_DIR="${OCR_HOME}/easyocr"
PYTHON_BIN="${PYTHON_BIN:-python3}"
WEB_USER="${WEB_USER:-www-data}"

echo "==> Budget Head OCR setup"
echo "App directory: ${APP_DIR}"

if ! command -v "${PYTHON_BIN}" >/dev/null 2>&1; then
  echo "ERROR: ${PYTHON_BIN} not found."
  echo "Install Python 3 first, e.g.: sudo apt install -y python3 python3-venv python3-pip"
  exit 1
fi

echo "==> Using: $("${PYTHON_BIN}" --version)"

if ! "${PYTHON_BIN}" -m pip --version >/dev/null 2>&1; then
  echo "ERROR: pip is not available for ${PYTHON_BIN}."
  echo "Install it with: sudo apt install -y python3-pip python3-venv"
  exit 1
fi

echo "==> Creating virtual environment: ${VENV_DIR}"
"${PYTHON_BIN}" -m venv "${VENV_DIR}"

echo "==> Installing OCR dependencies"
"${VENV_DIR}/bin/pip" install --upgrade pip
"${VENV_DIR}/bin/pip" install -r "${APP_DIR}/scripts/requirements-budget-head-ocr.txt"

echo "==> Preparing writable OCR directories"
mkdir -p "${OCR_MODEL_DIR}" "${OCR_HOME}/cache" "${OCR_HOME}/torch"
chmod -R 775 "${OCR_HOME}" || true
chmod -R go+rX "${VENV_DIR}" || true

echo "==> Pre-downloading EasyOCR models (first run only)"
HOME="${OCR_HOME}" \
XDG_CACHE_HOME="${OCR_HOME}/cache" \
TORCH_HOME="${OCR_HOME}/torch" \
BUDGET_HEAD_OCR_MODEL_DIR="${OCR_MODEL_DIR}" \
"${VENV_DIR}/bin/python3" - <<'PY'
import os
import easyocr

model_dir = os.environ["BUDGET_HEAD_OCR_MODEL_DIR"]
os.makedirs(model_dir, exist_ok=True)
easyocr.Reader(["en"], gpu=False, verbose=False, model_storage_directory=model_dir)
print("EasyOCR models ready:", model_dir)
PY

if id "${WEB_USER}" >/dev/null 2>&1; then
  echo "==> Assigning web user ownership for OCR runtime directories"
  chown -R "${WEB_USER}:${WEB_USER}" "${OCR_HOME}" "${VENV_DIR}" || true
fi

echo
echo "Setup complete."
echo "Add this to your .env file:"
echo "BUDGET_HEAD_PDF_PYTHON=${VENV_DIR}/bin/python3"
echo "BUDGET_HEAD_PDF_OCR_TIMEOUT=300"
echo
echo "Then run:"
echo "php artisan config:clear"
echo
echo "If upload still fails, verify web user can run OCR:"
echo "sudo -u ${WEB_USER} ${VENV_DIR}/bin/python3 --version"
