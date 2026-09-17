<?php

namespace App\Console\Commands;

use App\Models\FundCase;
use App\Support\CasePhotoProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Одноразовая миграция для уже загруженных фото: CasePhotoProcessor
 * (см. FundCaseResource) сжимает в JPEG только новые загрузки через
 * Filament — фото, загруженные раньше, остаются как есть, потенциально
 * тяжёлыми (несколько мегабайт), из-за чего на мобильном интернете донора
 * они долго грузятся или не догружаются вовсе. Команда прогоняет уже
 * существующие фото через тот же обработчик один раз.
 */
class ReencodeCasePhotosCommand extends Command
{
    protected $signature = 'case-photos:reencode {--dry-run : Show what would change without touching files or the database}';

    protected $description = 'Re-encode existing case photos as compressed JPEG (fixes slow/failing loads on mobile)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');

        $cases = FundCase::query()->whereNotNull('public_photo_paths')->get();

        $totalBefore = 0;
        $totalAfter = 0;
        $processedPhotos = 0;

        foreach ($cases as $case) {
            $paths = $case->public_photo_paths ?? [];

            if ($paths === []) {
                continue;
            }

            $newPaths = [];
            $changed = false;

            foreach ($paths as $path) {
                if (! $disk->exists($path)) {
                    $this->warn("Case #{$case->id}: missing file {$path}, skipping.");
                    $newPaths[] = $path;

                    continue;
                }

                $before = $disk->size($path);

                try {
                    $jpeg = CasePhotoProcessor::process($disk->get($path));
                } catch (Throwable $e) {
                    $this->warn("Case #{$case->id}: could not process {$path} ({$e->getMessage()}), keeping as is.");
                    $newPaths[] = $path;

                    continue;
                }

                $after = strlen($jpeg);
                $totalBefore += $before;
                $totalAfter += $after;
                $processedPhotos++;

                $newPath = 'case-photos/'.Str::ulid().'.jpg';

                $this->line(sprintf(
                    'Case #%d: %s (%s) -> %s (%s)',
                    $case->id, $path, $this->formatBytes($before), $newPath, $this->formatBytes($after),
                ));

                if (! $dryRun) {
                    $disk->put($newPath, $jpeg);
                    $disk->delete($path);
                }

                $newPaths[] = $newPath;
                $changed = true;
            }

            if ($changed && ! $dryRun) {
                $case->update(['public_photo_paths' => $newPaths]);
            }
        }

        if ($processedPhotos === 0) {
            $this->info('Nothing to re-encode.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s%d photo(s) re-encoded: %s -> %s (%.0f%% smaller).',
            $dryRun ? '[dry-run] ' : '',
            $processedPhotos,
            $this->formatBytes($totalBefore),
            $this->formatBytes($totalAfter),
            $totalBefore > 0 ? (1 - $totalAfter / $totalBefore) * 100 : 0,
        ));

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        return $bytes >= 1024 * 1024
            ? number_format($bytes / (1024 * 1024), 2).' MB'
            : number_format($bytes / 1024, 1).' KB';
    }
}
