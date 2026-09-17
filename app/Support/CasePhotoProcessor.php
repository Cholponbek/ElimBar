<?php

namespace App\Support;

use RuntimeException;

/**
 * Клиентский ресайз в FundCaseResource (imageResizeTargetWidth/Height,
 * filepond-plugin-image-resize) уменьшает разрешение перед загрузкой, но
 * не решает главную проблему: он сохраняет исходный формат файла. Телефон
 * отдаёт JPEG на несколько мегабайт — после клиентского ресайза до
 * 1600×900 это всё ещё несколько сотен килобайт-до-мегабайта каждое, и на
 * мобильном интернете донора фото либо долго грузится, либо не догружается
 * вовсе. Здесь — серверный проход: ужимаем до разумного разрешения и
 * перекодируем в JPEG с заданным качеством, независимо от того, что
 * прислал браузер (PNG/JPEG/GIF/WebP — всё, что понимает GD).
 */
class CasePhotoProcessor
{
    private const MAX_WIDTH = 1600;

    private const MAX_HEIGHT = 1600;

    private const JPEG_QUALITY = 72;

    /**
     * @return string Готовый JPEG (бинарные данные).
     *
     * @throws RuntimeException если GD не смог прочитать файл как изображение.
     */
    public static function process(string $binary): string
    {
        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            throw new RuntimeException('Не удалось прочитать файл как изображение (неподдерживаемый или повреждённый формат).');
        }

        $source = self::applyExifOrientation($source, $binary);

        $width = imagesx($source);
        $height = imagesy($source);

        // min(..., 1.0) — только уменьшаем, никогда не увеличиваем
        // маленькие фото сверх исходного размера.
        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height, 1.0);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($source);

        // Прогрессивный JPEG: браузер рисует размытое превью сразу по
        // первым полученным байтам и дорисовывает по мере догрузки,
        // вместо пустого места до самого последнего байта — на медленном
        // /дальнем соединении (тестовый сервер в Сингапуре, донор в КР)
        // ощущается быстрее при той же итоговой скорости передачи.
        imageinterlace($resized, true);

        ob_start();
        imagejpeg($resized, null, self::JPEG_QUALITY);
        $jpeg = ob_get_clean();
        imagedestroy($resized);

        if ($jpeg === false) {
            throw new RuntimeException('Не удалось сохранить обработанное изображение как JPEG.');
        }

        return $jpeg;
    }

    /**
     * Телефоны пишут поворот кадра в EXIF-тег Orientation, а не поворачивают
     * сами пиксели — GD его игнорирует, поэтому без этого шага часть фото
     * с телефона отображается повёрнутой на 90°/180°. exif_read_data умеет
     * читать только JPEG — для остальных форматов просто нечего поворачивать.
     */
    private static function applyExifOrientation(\GdImage $image, string $binary): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($binary));
        $orientation = $exif !== false ? ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated !== $image && $rotated !== false) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
