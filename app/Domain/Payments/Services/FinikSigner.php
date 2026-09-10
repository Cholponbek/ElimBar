<?php

namespace App\Domain\Payments\Services;

use RuntimeException;

/**
 * Реализация подписи запросов Finik (RSA-SHA256 поверх канонической
 * строки), портированная 1:1 с исходником официального npm-пакета
 * mancho.devs/authorizer (распакован и прочитан вручную — сама страница
 * документации не даёт этот код построчно). Канонический формат:
 *
 *   {метод в нижнем регистре}\n
 *   {decodeURI(путь)}\n
 *   host:{host}&x-api-{...}:{значение}&... (ключи x-api-* отсортированы)\n
 *   [query-параметры, если есть — не используются в этой интеграции]\n
 *   {JSON тела с ключами ВЕРХНЕГО уровня отсортированными по алфавиту;
 *    вложенные объекты (Data) сохраняют исходный порядок ключей — так же,
 *    как это делает Object.entries(body).sort() в JS, не рекурсивно}
 *
 * Один и тот же canonicalString() используется и для подписи исходящих
 * запросов (initiate), и для проверки подписи входящих вебхуков — чтобы
 * два места не могли разойтись в мелочах форматирования (кодировка
 * unicode, эскейп слэшей, сортировка), которые сломают подпись незаметно.
 */
final class FinikSigner
{
    public function canonicalString(string $method, string $path, string $host, array $apiHeaders, array $body = [], array $queryParams = []): string
    {
        $parts = [
            strtolower($method),
            rawurldecode($path),
            $this->headersLine($host, $apiHeaders),
        ];

        $queryString = $this->queryLine($queryParams);
        if ($queryString !== '') {
            $parts[] = $queryString;
        }

        $parts[] = $this->jsonBody($body);

        return implode("\n", $parts);
    }

    public function sign(string $data, string $privateKeyPem): string
    {
        $ok = openssl_sign($data, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Finik: RSA-SHA256 signing failed — check FINIK_PRIVATE_KEY_BASE64 is a valid PEM private key.');
        }

        return base64_encode($signature);
    }

    public function verify(string $data, string $signatureBase64, string $publicKeyPem): bool
    {
        $signature = base64_decode($signatureBase64, true);
        if ($signature === false) {
            return false;
        }

        return openssl_verify($data, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256) === 1;
    }

    private function headersLine(string $host, array $apiHeaders): string
    {
        $lines = ["host:{$host}"];

        $keys = array_keys($apiHeaders);
        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            $lines[] = strtolower($key).':'.$apiHeaders[$key];
        }

        return implode('&', $lines);
    }

    private function queryLine(array $queryParams): string
    {
        if ($queryParams === []) {
            return '';
        }

        $keys = array_keys($queryParams);
        sort($keys, SORT_STRING);

        $parts = array_map(
            fn (string $key) => rawurlencode($key).'='.rawurlencode((string) $queryParams[$key]),
            $keys,
        );

        return implode('&', $parts);
    }

    private function jsonBody(array $body): string
    {
        if ($body === []) {
            return '';
        }

        // Только верхний уровень — как Object.entries(body).sort() в JS,
        // вложенные массивы/объекты (Data) не трогаем.
        ksort($body, SORT_STRING);

        return json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
