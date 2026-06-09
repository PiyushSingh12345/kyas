<?php

return [
    'python_binary' => env('BUDGET_HEAD_PDF_PYTHON'),
    'ocr_timeout_seconds' => (int) env('BUDGET_HEAD_PDF_OCR_TIMEOUT', 300),
];
