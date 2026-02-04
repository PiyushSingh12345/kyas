# Fix: Column heading and data arrangement in Daily Sanction bulk upload preview

The preview was showing wrong column names (e.g. `COLUMN_6981BA098F71D`, `GRAND TOTAL :`) and misaligned data because the code was using the wrong Excel row as the table header. Apply the following changes in **`app/Http/Controllers/DailySanctionController.php`**.

---

## 1. Add this new method **before** `isTotalOrGrandTotalRow()` (around line 498)

```php
    /**
     * Return true if the row looks like the SPARSH data table header (S.No, SLS Scheme, Sanction Date, etc.).
     */
    private function rowLooksLikeTableHeader(array $row): bool
    {
        $nonEmpty = array_filter($row, function ($v) {
            return trim((string) $v) !== '';
        });
        if (count($nonEmpty) < 4) {
            return false;
        }
        $concat = ' ' . strtolower(implode(' ', array_map('strval', $row)));
        $markers = ['s.no', 'sls scheme', 'sanction date', 'sanction amount', 'function head', 'object head'];
        $found = 0;
        foreach ($markers as $m) {
            if (strpos($concat, $m) !== false) {
                $found++;
            }
        }
        return $found >= 2;
    }
```

---

## 2. In `isTotalOrGrandTotalRow()`, make two small edits

- **Line ~501:** Change  
  `if ($lower === 'grand total')`  
  to  
  `if (strpos($lower, 'grand total') !== false)`

- **Line ~507:** Change  
  `if (preg_match('/^total\s*\(\s*[^)]+\s*\)\s*$/i', $s))`  
  to  
  `if (preg_match('/^total\s*\(\s*[^)]+\s*\)\s*:?\s*$/i', $s))`  
  (allows trailing colon so "Total (2425) :" is excluded.)

---

## 3. Replace the "Detect table" block

**Delete** the block that starts with `/* Detect table: line 8 (row 7) = headers` and ends with the closing `}` of the `if (!empty($tableHeaders)) { ... }` block (the part that sets `$tableHeaders` and `$dataStartIndex` and normalizes headers).

**Insert** this in its place:

```php
            /* Find table header row: skip Grand Total/Total rows; use first row that looks like SPARSH headers */
            $tableHeaders = [];
            $dataStartIndex = 8;
            $headerRowIndex = null;
            for ($r = 7; $r < min(count($sheet), 20); $r++) {
                $candidate = $sheet[$r] ?? [];
                if ($this->isTotalOrGrandTotalRow($candidate)) {
                    continue;
                }
                if ($this->rowLooksLikeTableHeader($candidate)) {
                    $headerRowIndex = $r;
                    break;
                }
            }
            if ($headerRowIndex !== null) {
                $tableHeaders = array_values($sheet[$headerRowIndex] ?? []);
                $dataStartIndex = $headerRowIndex + 1;
            } else {
                $tableHeaders = array_values($sheet[7] ?? []);
                $dataStartIndex = 8;
            }

            /* Normalize headers: empty cells get Column_0, Column_1, etc.; deduplicate */
            $tableHeaders = array_map(function ($h, $idx) {
                $s = trim((string) $h);
                return $s !== '' ? $s : 'Column_' . $idx;
            }, $tableHeaders, array_keys($tableHeaders));
            if (!empty($tableHeaders)) {
                $used = [];
                foreach ($tableHeaders as $idx => $h) {
                    if (isset($used[$h])) {
                        $tableHeaders[$idx] = $h . '_' . $idx;
                    } else {
                        $used[$h] = true;
                    }
                }
            }
```

---

After these edits, save the file and re-upload your Excel. The parser will:

1. Skip rows that look like "Grand Total" or "Total (2425) :" when choosing the header row.
2. Use the first row (from line 8 onward) that looks like real SPARSH headers (S.No, SLS Scheme, Sanction Date, etc.).
3. Exclude "Total (2425) :" and similar rows from the data.
4. Use stable placeholders for empty header cells (Column_0, Column_1) instead of random IDs.

The preview should then show the correct column headings and aligned data as in your screenshot.
