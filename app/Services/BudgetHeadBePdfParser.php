<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class BudgetHeadBePdfParser
{
    public function parse(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        if (trim($text) === '') {
            return [
                'type' => 'pdf_be',
                'error' => 'No readable text found in PDF. Please upload a text-based PDF file.',
                'financial_year' => null,
                'structured_data' => [],
                'total_items' => 0,
            ];
        }

        $financialYear = $this->extractFinancialYear($text);
        $section = $this->extractKrishonnatiSection($text);

        if ($section === '') {
            return [
                'type' => 'pdf_be',
                'error' => 'Krishonnati Yojna section not found in PDF.',
                'financial_year' => $financialYear,
                'structured_data' => [],
                'total_items' => 0,
            ];
        }

        $structured = $this->deduplicateByCode($this->parseBudgetLines($section));
        $pdfTotal = $this->extractPdfSectionTotal($text);

        if (count($structured) === 0) {
            return [
                'type' => 'pdf_be',
                'error' => 'No budget head records found below Krishonnati Yojna.',
                'financial_year' => $financialYear,
                'structured_data' => [],
                'total_items' => 0,
            ];
        }

        $extractedTotal = $this->sumCurrentYearAmounts($structured);

        return [
            'type' => 'pdf_be',
            'financial_year' => $financialYear,
            'structured_data' => $structured,
            'total_items' => count($structured),
            'pdf_section_total' => $pdfTotal,
            'extracted_total' => $extractedTotal,
        ];
    }

    private function extractFinancialYear(string $text): ?string
    {
        if (preg_match('/Head\s+of\s+account\s*\/\s*Scheme\s*&\s*Programme[^\n]*/i', $text, $headerLine)) {
            if (preg_match_all('/BE\s*(\d{4})-(\d{2})/i', $headerLine[0], $years) && !empty($years[1])) {
                $index = count($years[1]) - 1;

                return $years[1][$index] . '-' . $years[2][$index];
            }
        }

        if (preg_match('/Details of BE (\d{4})-(\d{2})/i', $text, $match)) {
            return $match[1] . '-' . $match[2];
        }

        if (preg_match_all('/BE\s*(\d{4})-(\d{2})/i', $text, $years) && !empty($years[1])) {
            $index = count($years[1]) - 1;

            return $years[1][$index] . '-' . $years[2][$index];
        }

        return null;
    }

    private function extractKrishonnatiSection(string $text): string
    {
        if (preg_match(
            '/Krishonnati\s+Yojna[^\n]*\n(.*?)(?:Krishonnati\s+Yojna\s+Total\b)/is',
            $text,
            $matches
        )) {
            return trim($matches[1]);
        }

        if (preg_match(
            '/Krishonnati\s+Yojna[^\n]*\n(.*?)(?:National\s+Mission\s+on\s+Natural\s+Farming\b)/is',
            $text,
            $matches
        )) {
            return trim($matches[1]);
        }

        if (preg_match('/Krishonnati\s+Yojna[^\n]*\n(.*)/is', $text, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function parseBudgetLines(string $section): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $section))));
        $structured = [];

        for ($i = 0; $i < count($lines); $i++) {
            $currentLine = trim($lines[$i]);

            if ($this->shouldSkipLine($currentLine)) {
                continue;
            }

            if (!preg_match('/^(\d{12,15})-(.+)$/', $currentLine, $parts)) {
                continue;
            }

            $code = trim($parts[1]);

            if ($this->isPhantomDuplicateLine($lines, $i, $code)) {
                continue;
            }
            $fullDescription = trim($parts[2]);
            $bePrevious = null;
            $beCurrent = null;
            $nextLineIndex = $i + 1;

            while ($nextLineIndex < count($lines) &&
                isset($lines[$nextLineIndex]) &&
                !preg_match('/^(\d{12,15})-(.+)$/', $lines[$nextLineIndex]) &&
                !$this->isStandaloneAmountLine($lines[$nextLineIndex])) {

                $nextLine = trim($lines[$nextLineIndex]);

                if ($this->shouldSkipLine($nextLine)) {
                    $nextLineIndex++;
                    continue;
                }

                $inlineAmounts = $this->extractTrailingAmounts($nextLine);
                if ($inlineAmounts['be_previous'] !== null || $inlineAmounts['be_current'] !== null) {
                    if ($inlineAmounts['be_previous'] !== null) {
                        $bePrevious = $inlineAmounts['be_previous'];
                    }
                    if ($inlineAmounts['be_current'] !== null) {
                        $beCurrent = $inlineAmounts['be_current'];
                    }
                    $fullDescription .= ' ' . $inlineAmounts['description'];
                    break;
                }

                $fullDescription .= ' ' . $nextLine;
                $nextLineIndex++;
            }

            if ($bePrevious === null && $beCurrent === null && $nextLineIndex < count($lines) &&
                $this->isStandaloneAmountLine($lines[$nextLineIndex])) {
                $standaloneLine = trim($lines[$nextLineIndex]);
                $nextLineIndex++;
                $standaloneAmounts = $this->parseStandaloneAmountLine($standaloneLine);

                if ($standaloneAmounts['be_previous'] !== null) {
                    $bePrevious = $standaloneAmounts['be_previous'];
                    $beCurrent = $standaloneAmounts['be_current'];
                } elseif ($nextLineIndex < count($lines) && $this->isStandaloneAmountLine($lines[$nextLineIndex])) {
                    $bePrevious = $standaloneLine;
                    $beCurrent = trim($lines[$nextLineIndex]);
                    $nextLineIndex++;
                } else {
                    $beCurrent = $standaloneLine;
                }
            }

            $inlineAmounts = $this->extractTrailingAmounts($fullDescription);
            $cleanItem = $inlineAmounts['description'];

            if ($inlineAmounts['be_previous'] !== null) {
                $bePrevious = $inlineAmounts['be_previous'];
            }
            if ($inlineAmounts['be_current'] !== null) {
                $beCurrent = $inlineAmounts['be_current'];
            }

            $cleanItem = str_replace("\t", ' ', $cleanItem);
            $cleanItem = trim($cleanItem);

            $structured[] = [
                'code' => $code,
                'item' => $cleanItem,
                'be_2024_25' => $bePrevious,
                'be_2025_26' => $beCurrent,
            ];

            $i = $nextLineIndex - 1;
        }

        return $structured;
    }

    private function parseStandaloneAmountLine(string $line): array
    {
        $line = trim($line);
        $amountPattern = '\d+(?:\.\d{1,2})?';

        if (preg_match('/^(' . $amountPattern . ')\s+(' . $amountPattern . ')$/', $line, $match)) {
            return [
                'be_previous' => $match[1],
                'be_current' => $match[2],
            ];
        }

        return [
            'be_previous' => null,
            'be_current' => $line,
        ];
    }

    private function isStandaloneAmountLine(string $line): bool
    {
        $line = trim($line);

        if ($line === '' || preg_match('/^(\d{12,15})-/', $line)) {
            return false;
        }

        return (bool) preg_match('/^\d+(?:\.\d{1,2})?(?:\s+\d+(?:\.\d{1,2})?)?$/', $line);
    }

    /**
     * Extract trailing budget amounts from a description line.
     * Supports decimal (50.00) and whole-number (3390) formats.
     */
    private function extractTrailingAmounts(string $text): array
    {
        $text = trim(str_replace("\t", ' ', $text));
        $amountPattern = '\d+(?:\.\d{1,2})?';

        if (preg_match('/^(.+?)\s+(' . $amountPattern . ')\s+(' . $amountPattern . ')\s*$/', $text, $match)) {
            return [
                'description' => trim($match[1]),
                'be_previous' => $match[2],
                'be_current' => $match[3],
            ];
        }

        if (preg_match('/^(.+?)\s+(' . $amountPattern . ')\s*$/', $text, $match)) {
            return [
                'description' => trim($match[1]),
                'be_previous' => null,
                'be_current' => $match[2],
            ];
        }

        return [
            'description' => $text,
            'be_previous' => null,
            'be_current' => null,
        ];
    }

    private function shouldSkipLine(string $line): bool
    {
        return stripos($line, 'Details of BE') !== false ||
            stripos($line, 'Rs Lakh') !== false ||
            stripos($line, 'Schemes/Programmes') !== false ||
            stripos($line, 'Head of account') !== false ||
            stripos($line, 'Krishonnati Yojna Total') !== false;
    }

    /**
     * PDF text extraction sometimes splits one row into two lines with the same code.
     * Keep the following line when it carries the amount for the same budget head.
     */
    private function isPhantomDuplicateLine(array $lines, int $index, string $code): bool
    {
        for ($j = $index + 1; $j < count($lines); $j++) {
            $nextLine = trim($lines[$j]);

            if ($this->shouldSkipLine($nextLine)) {
                continue;
            }

            if (!preg_match('/^(\d{12,15})-(.+)$/', $nextLine, $parts)) {
                return false;
            }

            if (trim($parts[1]) !== $code) {
                return false;
            }

            $inlineAmounts = $this->extractTrailingAmounts(trim($parts[2]));

            return $inlineAmounts['be_current'] !== null || $inlineAmounts['be_previous'] !== null;
        }

        return false;
    }

    private function deduplicateByCode(array $structured): array
    {
        $byCode = [];

        foreach ($structured as $item) {
            $code = $item['code'];

            if (!isset($byCode[$code])) {
                $byCode[$code] = $item;
                continue;
            }

            $existingHasAmount = $this->hasAmount($byCode[$code]['be_2025_26']);
            $newHasAmount = $this->hasAmount($item['be_2025_26']);

            if ($newHasAmount && !$existingHasAmount) {
                $byCode[$code] = $item;
            }
        }

        return array_values($byCode);
    }

    private function hasAmount($value): bool
    {
        return $value !== null && $value !== '';
    }

    private function sumCurrentYearAmounts(array $structured): float
    {
        $total = 0.0;

        foreach ($structured as $item) {
            if ($this->hasAmount($item['be_2025_26'] ?? null)) {
                $total += (float) $item['be_2025_26'];
            }
        }

        return $total;
    }

    private function extractPdfSectionTotal(string $text): ?float
    {
        if (preg_match('/Krishonnati\s+Yojna\s+Total[^\d]*([\d,]+(?:\.\d+)?)/i', $text, $match)) {
            return (float) str_replace(',', '', $match[1]);
        }

        return null;
    }
}
