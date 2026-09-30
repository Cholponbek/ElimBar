<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #101318; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin: 18px 0 8px; border-bottom: 1px solid #DCE6F0; padding-bottom: 4px; }
        .muted { color: #5B6472; }
        table.kv { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.kv td { padding: 4px 8px 4px 0; vertical-align: top; }
        table.kv td.label { width: 180px; color: #5B6472; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #F5F8FC; }
        .photos img { width: 150px; height: 110px; object-fit: cover; margin: 0 8px 8px 0; border: 1px solid #DCE6F0; }
        .doc-row { padding: 4px 0; border-bottom: 1px solid #F0F3F7; }
        .doc-row img { width: 40px; height: 40px; object-fit: cover; margin-right: 8px; vertical-align: middle; }
        .footer { margin-top: 24px; font-size: 10px; color: #8B94A3; }
    </style>
</head>
<body>
    <h1>Отчёт по закрытию кейса</h1>
    <p class="muted">{{ $case->public_title['ru'] ?? $case->public_title['ky'] ?? '' }}</p>

    <h2>Кейс</h2>
    <table class="kv">
        <tr>
            <td class="label">Краткое описание</td>
            <td>{{ \Illuminate\Support\Str::limit(strip_tags($case->public_story['ru'] ?? $case->public_story['ky'] ?? ''), 400, '…') ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Нужно было собрать</td>
            <td>{{ $case->budget_minor === null ? 'Не ограничен' : number_format($case->budget_minor / 100, 0, '.', ' ').' сом' }}</td>
        </tr>
        <tr>
            <td class="label">Собрано</td>
            <td>{{ number_format($case->allocated_minor / 100, 0, '.', ' ') }} сом</td>
        </tr>
        <tr>
            <td class="label">Дата начала</td>
            <td>{{ $case->created_at->translatedFormat('d.m.Y') }}</td>
        </tr>
        <tr>
            <td class="label">Дата окончания</td>
            <td>{{ $case->end_date?->translatedFormat('d.m.Y') ?? 'Бессрочный' }}</td>
        </tr>
        <tr>
            <td class="label">Статус</td>
            <td><span class="status">{{ ['draft' => 'Черновик', 'active' => 'Активен', 'closed' => 'Закрыт'][$case->status] ?? $case->status }}</span></td>
        </tr>
        @if ($case->closed_at)
            <tr>
                <td class="label">Дата закрытия</td>
                <td>{{ $case->closed_at->translatedFormat('d.m.Y') }}</td>
            </tr>
        @endif
    </table>

    <h2>Отчёт о проведённых мероприятиях</h2>
    <p>{{ $case->closure_report_description['ru'] ?? $case->closure_report_description['ky'] ?? 'Описание не заполнено.' }}</p>

    @if ($reportPhotos->isNotEmpty())
        <div class="photos">
            @foreach ($reportPhotos as $photo)
                <img src="{{ $photo }}" alt="Фото отчёта">
            @endforeach
        </div>
    @endif

    <h2>Финансовые и юридические документы</h2>
    @if ($documents->isEmpty())
        <p class="muted">Документы не загружены.</p>
    @else
        @foreach ($documents as $document)
            <div class="doc-row">
                @if ($document['thumbnail'])
                    <img src="{{ $document['thumbnail'] }}" alt="">
                @endif
                {{ $document['name'] }}
            </div>
        @endforeach
    @endif

    <p class="footer">Сформировано {{ now()->translatedFormat('d.m.Y H:i') }} · ElimBar</p>
</body>
</html>
