#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PYTHON="${BUDGET_HEAD_PDF_PYTHON:-${APP_DIR}/.venv-budget-head-ocr/bin/python3}"

if [[ ! -x "${PYTHON}" ]]; then
  PYTHON="$(command -v python3 || true)"
fi

if [[ -z "${PYTHON}" || ! -x "${PYTHON}" ]]; then
  echo '{"structured_data":[],"financial_years":[],"total_items":0,"error":"Python 3 not found on server."}' >&2
  exit 1
fi

exec "${PYTHON}" "${APP_DIR}/scripts/extract_budget_head_table_pdf.py" "$1"
