<?php

use App\Domain\Payments\Services\FinikSigner;

/**
 * FinikSigner портирует канонический формат из исходника официального
 * npm-пакета Finik (@mancho.devs/authorizer, class Signer, метод getData())
 * — не из прозы документации, где этот формат не расписан по байтам.
 * Эти тесты фиксируют формат буквально, без сети и без реальных ключей
 * Finik: RSA-подпись/проверка гоняются на сгенерированной здесь же паре.
 */
it('builds the canonical string as method, path, headers-line, then sorted-key JSON body, newline-joined', function () {
    $signer = new FinikSigner;

    $data = $signer->canonicalString(
        method: 'POST',
        path: '/v1/payment',
        host: 'beta.api.acquiring.averspay.kg',
        apiHeaders: ['x-api-key' => 'abc123', 'x-api-timestamp' => '1717000000000'],
        body: ['PaymentId' => 'p-1', 'Amount' => 500, 'Data' => ['accountId' => 'acc-1', 'name_en' => 'Fund']],
    );

    $expected = implode("\n", [
        'post',
        '/v1/payment',
        'host:beta.api.acquiring.averspay.kg&x-api-key:abc123&x-api-timestamp:1717000000000',
        '{"Amount":500,"Data":{"accountId":"acc-1","name_en":"Fund"},"PaymentId":"p-1"}',
    ]);

    expect($data)->toBe($expected);
});

it('sorts only top-level body keys, leaving nested object key order untouched', function () {
    $signer = new FinikSigner;

    $data = $signer->canonicalString(
        method: 'post',
        path: '/v1/payment',
        host: 'h',
        apiHeaders: [],
        body: ['Z' => 1, 'A' => ['second' => 2, 'first' => 1]],
    );

    expect($data)->toContain('{"A":{"second":2,"first":1},"Z":1}');
});

it('omits the query-string line entirely when there are no query params', function () {
    $signer = new FinikSigner;

    $data = $signer->canonicalString('post', '/v1/payment', 'h', ['x-api-key' => 'k'], ['a' => 1]);

    expect(substr_count($data, "\n"))->toBe(3, 'method / path / headers / body = 4 lines = 3 newlines when there is no query string');
});

it('produces a signature that verifies with the matching public key and fails with a different one', function () {
    $signer = new FinikSigner;

    $keyA = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keyA, $privateA);
    $publicA = openssl_pkey_get_details($keyA)['key'];

    $keyB = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $publicB = openssl_pkey_get_details($keyB)['key'];

    $data = $signer->canonicalString('post', '/v1/payment', 'h', ['x-api-key' => 'k'], ['a' => 1]);
    $signature = $signer->sign($data, $privateA);

    expect($signer->verify($data, $signature, $publicA))->toBeTrue();
    expect($signer->verify($data, $signature, $publicB))->toBeFalse();
});

it('fails verification when the signed data changes but the signature does not', function () {
    $signer = new FinikSigner;

    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $privateKey);
    $publicKey = openssl_pkey_get_details($key)['key'];

    $signature = $signer->sign('original data', $privateKey);

    expect($signer->verify('tampered data', $signature, $publicKey))->toBeFalse();
});
