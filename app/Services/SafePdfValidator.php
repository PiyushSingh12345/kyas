<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class SafePdfValidator
{
    /**
     * Validate that uploaded PDF bytes are safe for server-side text extraction.
     *
     * Blocks executable PDF objects while allowing common government/budget PDFs
     * that include benign catalog entries such as OpenAction or Additional Actions.
     */
    public function assertSafe(string $binaryContent, string $field = 'file'): void
    {
        if (! str_starts_with($binaryContent, '%PDF-')) {
            throw ValidationException::withMessages([
                $field => ['Uploaded file is not a valid PDF document.'],
            ]);
        }

        foreach ($this->dangerousObjectPatterns() as $pattern) {
            if (preg_match($pattern, $binaryContent)) {
                throw ValidationException::withMessages([
                    $field => ['PDF contains potentially dangerous embedded scripts or actions.'],
                ]);
            }
        }

        if (preg_match('/<script\b|javascript:/i', $binaryContent)) {
            throw ValidationException::withMessages([
                $field => ['PDF contains potentially dangerous embedded scripts or actions.'],
            ]);
        }
    }

    /**
     * Match PDF name objects that embed executable content, not arbitrary binary substrings.
     *
     * @return array<int, string>
     */
    private function dangerousObjectPatterns(): array
    {
        return [
            '/\/JavaScript\s*[<(]/i',
            '/\/JS\s*[\(<]/i',
            '/\/Launch\s*<</i',
            '/\/RichMedia\s*<</i',
            '/\/SubmitForm\s*<</i',
            '/\/ImportData\s*<</i',
        ];
    }
}
