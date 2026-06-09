#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VENV_DIR="${APP_DIR}/.venv-budget-head-ocr"
PYTHON_BIN="${PYTHON_BIN:-python3}"

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

echo
echo "Setup complete."
echo "Add this to your .env file:"
echo "BUDGET_HEAD_PDF_PYTHON=${VENV_DIR}/bin/python3"
echo "BUDGET_HEAD_PDF_OCR_TIMEOUT=300"
echo
echo "Then run:"
echo "php artisan config:clear"
