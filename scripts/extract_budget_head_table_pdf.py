#!/usr/bin/env python3
"""Extract structured budget-head table rows from image PDFs using spatial OCR."""

from __future__ import annotations

import io
import json
import re
import sys
import warnings

warnings.filterwarnings("ignore")

import fitz


def correct_ocr_amount_digits(digits: str) -> str:
    """Remove stray trailing '1' OCR often adds after amounts ending in 0."""
    if (
        len(digits) >= 4
        and "." not in digits
        and digits.endswith("1")
        and digits[-2] == "0"
    ):
        return digits[:-1]
    return digits


def normalize_amount(value: str) -> str | None:
    cleaned = re.sub(r"[^\d.]", "", value or "")
    if cleaned in ("", ".", "0", "0.0", "0.00"):
        return None

    if "." not in cleaned:
        cleaned = correct_ocr_amount_digits(cleaned)

    if cleaned in ("", "0", "0.0", "0.00"):
        return None

    return cleaned


def parse_left_cell(text: str) -> dict | None:
    text = re.sub(r"[_\|]", "-", text).strip()
    if not text:
        return None

    if re.search(r"Krishonnati\s+Yojna\s+Total", text, re.I):
        return {"type": "total"}

    if re.search(r"^Krishonnati\s+Yojna\b", text, re.I):
        return {"type": "section"}

    year_match = re.search(r"Head of account.*?BE\s*(\d{4}-\d{2})", text, re.I)
    if year_match:
        return {"type": "header", "financial_year": year_match.group(1)}

    year_match = re.search(r"\bBE\s*(\d{4}-\d{2})\b", text, re.I)
    if year_match and "Head of account" in text:
        return {"type": "header", "financial_year": year_match.group(1)}

    code_match = re.search(r"(\d{15})[-\s]+(.+)", text)
    if code_match:
        item = re.sub(r"\s+", " ", code_match.group(2)).strip(" -")
        return {
            "type": "record",
            "code": code_match.group(1),
            "item": item,
        }

    return None


def group_spatial_rows(items: list[tuple[int, int, str]], page_width: int) -> list[tuple[str, str]]:
    rows: dict[int, list[tuple[int, str]]] = {}
    split_x = int(page_width * 0.62)

    for left, top, text in items:
        row_key = None
        for key in rows:
            if abs(key - top) <= 18:
                row_key = key
                break
        if row_key is None:
            row_key = top
            rows[row_key] = []

        rows[row_key].append((left, text))

    parsed_rows: list[tuple[str, str]] = []
    for top in sorted(rows.keys()):
        cells = sorted(rows[top], key=lambda item: item[0])
        left_parts = [text for left, text in cells if left < split_x]
        right_parts = [text for left, text in cells if left >= split_x]
        left = " ".join(left_parts).strip()
        right = " ".join(right_parts).strip()
        if left or right:
            parsed_rows.append((left, right))

    return parsed_rows


def ocr_page_with_tesseract(pix) -> list[tuple[str, str]]:
    from PIL import Image
    import pytesseract
    from pytesseract import Output

    img = Image.open(io.BytesIO(pix.tobytes("png")))
    data = pytesseract.image_to_data(img, output_type=Output.DICT)
    items: list[tuple[int, int, str]] = []

    for index, text in enumerate(data["text"]):
        text = text.strip()
        if not text:
            continue

        try:
            confidence = int(float(data["conf"][index]))
        except ValueError:
            confidence = -1

        if confidence >= 0 and confidence < 20:
            continue

        items.append((data["left"][index], data["top"][index], text))

    return group_spatial_rows(items, img.width)


def extract_structured_rows(pdf_path: str) -> list[dict]:
    doc = fitz.open(pdf_path)
    structured_rows: list[dict] = []
    current_financial_year: str | None = None
    in_section = False
    detected_years: list[str] = []
    ocr_errors: list[str] = []

    def register_year(year: str) -> None:
        nonlocal current_financial_year
        if year not in detected_years:
            detected_years.append(year)
        current_financial_year = year

    def detect_year(left: str, right: str) -> str | None:
        for cell in (left, right):
            year_match = re.search(r"\bBE\s*(\d{4}-\d{2})\b", cell, re.I)
            if year_match:
                return year_match.group(1)
        return None

    def process_row(left: str, right: str) -> bool:
        nonlocal in_section

        year = detect_year(left, right)
        if year:
            register_year(year)
            return False

        parsed = parse_left_cell(left)
        if parsed is None:
            return False

        if parsed["type"] == "header":
            register_year(parsed["financial_year"])
            return False

        if parsed["type"] == "section":
            in_section = True
            return False

        if parsed["type"] == "total":
            return True

        if parsed["type"] == "record" and in_section:
            structured_rows.append(
                {
                    "code": parsed["code"],
                    "item": parsed["item"],
                    "budget_amount": normalize_amount(right),
                    "financial_year": current_financial_year,
                }
            )

        return False

    for page in doc:
        page_text = page.get_text().strip()
        if page_text:
            for line in page_text.splitlines():
                left = line.strip()
                right = ""
                if "\t" in line:
                    left, right = [part.strip() for part in line.split("\t", 1)]
                if process_row(left, right):
                    break
            continue

        try:
            pix = page.get_pixmap(matrix=fitz.Matrix(2, 2))
            page_rows = ocr_page_with_tesseract(pix)
            for left, right in page_rows:
                if process_row(left, right):
                    break
        except Exception as exc:
            ocr_errors.append(str(exc))
            continue

    doc.close()

    if not structured_rows and ocr_errors:
        unique_errors = list(dict.fromkeys(ocr_errors))
        hint = unique_errors[0]
        if "tesseract" in hint.lower():
            hint = (
                "Tesseract OCR is not installed. Run: sudo apt install -y tesseract-ocr "
                "and pip install -r scripts/requirements-budget-head-ocr.txt"
            )
        raise RuntimeError(hint)

    if len(detected_years) == 1:
        only_year = detected_years[0]
        for row in structured_rows:
            row["financial_year"] = only_year
    elif detected_years:
        fallback_year = detected_years[-1]
        for row in structured_rows:
            if not row.get("financial_year"):
                row["financial_year"] = fallback_year

    return structured_rows


if __name__ == "__main__":
    empty_payload = {
        "structured_data": [],
        "financial_years": [],
        "total_items": 0,
    }

    if len(sys.argv) < 2:
        print(json.dumps(empty_payload), end="")
        sys.exit(1)

    try:
        rows = extract_structured_rows(sys.argv[1])
        financial_years = []
        for row in rows:
            year = row.get("financial_year")
            if year and year not in financial_years:
                financial_years.append(year)

        print(
            json.dumps(
                {
                    "structured_data": rows,
                    "financial_years": financial_years,
                    "total_items": len(rows),
                },
                ensure_ascii=False,
            ),
            end="",
        )
    except Exception as exc:
        print(
            json.dumps(
                {
                    **empty_payload,
                    "error": str(exc),
                },
                ensure_ascii=False,
            ),
            end="",
        )
        sys.exit(1)
