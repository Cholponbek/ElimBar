<?php

use App\Support\CasePhotoProcessor;

/**
 * PNG сжимает плоский цвет почти без потерь до нескольких КБ — на таком
 * входе JPEG после ресайза мог бы оказаться даже тяжелее оригинала, что
 * не отражает реальные фото. Шум по пикселям даёт PNG и JPEG сопоставимую
 * с настоящей фотографией разницу в сжимаемости.
 */
function makeTestPng(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    mt_srand(42);
    for ($x = 0; $x < $width; $x += 4) {
        for ($y = 0; $y < $height; $y += 4) {
            $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagefilledrectangle($image, $x, $y, $x + 3, $y + 3, $color);
        }
    }
    ob_start();
    imagepng($image);
    $png = ob_get_clean();
    imagedestroy($image);

    return $png;
}

it('re-encodes a PNG as a smaller JPEG capped to the max dimension', function () {
    $png = makeTestPng(3000, 2000);

    $jpeg = CasePhotoProcessor::process($png);

    expect(strlen($jpeg))->toBeLessThan(strlen($png));

    $size = getimagesizefromstring($jpeg);
    expect($size[2])->toBe(IMAGETYPE_JPEG);
    // Долгая сторона упирается в MAX_WIDTH/MAX_HEIGHT (1600), пропорции сохраняются.
    expect(max($size[0], $size[1]))->toBe(1600);
    expect($size[0] / $size[1])->toBeGreaterThan(1.49)->toBeLessThan(1.51);
});

it('never upscales an image smaller than the cap', function () {
    $png = makeTestPng(400, 300);

    $jpeg = CasePhotoProcessor::process($png);

    $size = getimagesizefromstring($jpeg);
    expect([$size[0], $size[1]])->toBe([400, 300]);
});

it('throws when the input is not a decodable image', function () {
    CasePhotoProcessor::process('this is definitely not an image');
})->throws(RuntimeException::class);
