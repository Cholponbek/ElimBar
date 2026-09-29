<?php

namespace App\Support;

use App\Models\FundCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Печатная форма отчёта по закрытию кейса ("Отчёт" → "Скачать PDF" на
 * странице закрытия). Фото/документы встраиваются как data: URI (base64) —
 * так это работает одинаково для локального диска и S3, dompdf не должен
 * лезть в файловую систему или по сети сам.
 */
class CaseClosurePdfBuilder
{
    public static function build(FundCase $case): string
    {
        $reportPhotos = collect($case->closure_report_photo_paths ?? [])
            ->map(fn (string $path) => self::imageDataUri('public', $path))
            ->filter()
            ->values();

        $documents = collect($case->financial_documents_paths ?? [])
            ->map(function (string $path) {
                $isImage = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg']);

                return [
                    'name' => basename($path),
                    'thumbnail' => $isImage ? self::imageDataUri('proofs', $path) : null,
                ];
            })
            ->values();

        $html = view('pdf.case-closure', [
            'case' => $case,
            'reportPhotos' => $reportPhotos,
            'documents' => $documents,
        ])->render();

        return Pdf::loadHTML($html)->output();
    }

    private static function imageDataUri(string $disk, string $path): ?string
    {
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $mime = Storage::disk($disk)->mimeType($path) ?: 'image/jpeg';
        $contents = base64_encode(Storage::disk($disk)->get($path));

        return "data:{$mime};base64,{$contents}";
    }
}
