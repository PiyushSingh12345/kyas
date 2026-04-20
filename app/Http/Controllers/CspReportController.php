<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            $payload = $request->all();
        }

        // Keep only useful report fields and cap size to avoid log abuse.
        $report = $payload['csp-report'] ?? $payload;
        if (! is_array($report)) {
            $report = ['raw' => (string) $report];
        }

        $allowedKeys = [
            'document-uri',
            'referrer',
            'violated-directive',
            'effective-directive',
            'original-policy',
            'blocked-uri',
            'status-code',
            'line-number',
            'column-number',
            'source-file',
            'script-sample',
            'disposition',
        ];

        $sanitized = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $report)) {
                $value = is_scalar($report[$key]) ? (string) $report[$key] : json_encode($report[$key]);
                $sanitized[$key] = mb_substr($value ?? '', 0, 1000);
            }
        }

        Log::warning('CSP violation reported', $sanitized);

        return response()->noContent();
    }
}

