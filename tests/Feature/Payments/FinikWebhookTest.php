<?php

use App\Domain\Payments\Adapters\FinikPaymentGateway;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Services\FinikSigner;
use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\FundCase;
use App\Models\PaymentEvent;
use Illuminate\Support\Facades\DB;

/**
 * Finik шлёт вебхук только при успешной оплате (см. FinikWebhookController).
 * Здесь мы играем роль Finik: подписываем payload тем же FinikSigner
 * приватным ключом тестовой пары, конфигурируем адаптер с публичным —
 * реальные ключи/сеть не нужны, сам механизм проверки подписи и есть
 * то, что проверяется.
 *
 * confirmDonation() внутри контроллера читает/пишет через connection
 * pgsql_staff — отдельная физическая сессия Postgres, которую
 * RefreshDatabase не оборачивает (оно оборачивает только 'pgsql', см.
 * тот же манёвр и комментарий в PublicDonationFlowTest). Поэтому кейс/
 * бенефициар/донат для тестов, которым нужен реальный round-trip через
 * pgsql_staff, тоже заводятся через pgsql_staff — иначе FK на ещё не
 * закоммиченную в другой сессии строку просто не пройдёт проверку.
 *
 * Эти строки НЕ подчищаются после теста и это осознанно, не недосмотр:
 * allocations в принципе не подчистить — forbid_allocation_mutation()
 * запрещает DELETE в БД тем же триггером, что запрещает UPDATE (сама
 * суть append-only ledger'а — см. Allocation), а donations/cases с уже
 * привязанной allocation держит restrictOnDelete по FK. Реальная система
 * не даёт стереть историю донатов — тестовая БД не исключение. Разовый
 * мусор в elimbar_testing от прогона этих тестов ожидаем и безвреден:
 * ни на одно поле здесь нет UNIQUE-констрейнта, второй прогон не упадёт.
 */
beforeEach(function () {
    $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keyPair, $this->finikPrivateKey);
    $this->finikPublicKey = openssl_pkey_get_details($keyPair)['key'];
    $this->webhookUrl = 'https://elimbar.test/webhooks/finik';
    $this->apiKey = 'test-api-key';

    $this->app->instance(PaymentGateway::class, new FinikPaymentGateway(
        signer: new FinikSigner,
        baseUrl: 'https://beta.api.acquiring.averspay.kg',
        accountId: 'acc-1',
        apiKey: $this->apiKey,
        privateKey: 'unused in these tests — only verifyWebhookSignature() is exercised',
        webhookPublicKey: $this->finikPublicKey,
        webhookUrl: $this->webhookUrl,
        merchantName: 'Тест фонд',
    ));
});

/**
 * Кейс + донор + донат целиком в сессии pgsql_staff — см. комментарий
 * выше файла. beneficiary_id/donor_id передаются явно, а не через
 * вложенные Xxx::factory() внутри *Factory::raw(): Laravel всё равно
 * персистит их (даже у raw()) через ИХ собственное дефолтное подключение
 * ('pgsql'), что рвёт FK при вставке в pgsql_staql_staff тем же путём,
 * что описан в комментарии над файлом.
 */
function createStagedDonation(array $donationAttributes): array
{
    $beneficiary = Beneficiary::on('pgsql_staff')->create(Beneficiary::factory()->raw());

    $case = FundCase::on('pgsql_staff')->create([
        ...FundCase::factory()->raw(),
        'beneficiary_id' => $beneficiary->id,
        'budget_minor' => 100_000_00,
    ]);

    $donor = Donor::on('pgsql_staff')->create(Donor::factory()->raw());

    $donation = Donation::on('pgsql_staff')->create([
        ...Donation::factory()->raw(),
        ...$donationAttributes,
        'donor_id' => $donor->id,
        'case_id' => $case->id,
    ]);

    return [$case, $donation];
}

function signFinikWebhook(array $payload, string $privateKey, string $webhookUrl, string $apiKey): array
{
    $signer = new FinikSigner;
    $apiHeaders = ['x-api-key' => $apiKey, 'x-api-timestamp' => (string) (int) round(microtime(true) * 1000)];

    $data = $signer->canonicalString(
        'POST',
        parse_url($webhookUrl, PHP_URL_PATH),
        parse_url($webhookUrl, PHP_URL_HOST),
        $apiHeaders,
        $payload,
    );

    return [...$apiHeaders, 'signature' => $signer->sign($data, $privateKey)];
}

it('confirms a pending donation and allocates it to its case on a valid signed webhook', function () {
    [$case, $donation] = createStagedDonation([
        'amount_minor' => 1_500_00,
        'status' => 'pending',
        'provider' => 'finik',
        'provider_ref' => 'pay-abc-123',
        'paid_at' => null,
    ]);

    $payload = [
        'transactionId' => 'txn-1',
        'status' => 'success',
        'amount' => 1500,
        'fields' => ['paymentId' => 'pay-abc-123'],
    ];
    $headers = signFinikWebhook($payload, $this->finikPrivateKey, $this->webhookUrl, $this->apiKey);

    $response = $this->postJson('/webhooks/finik', $payload, $headers);

    $response->assertOk();
    expect(Donation::on('pgsql_staff')->find($donation->id)->status)->toBe('completed');
    expect(Donation::on('pgsql_staff')->find($donation->id)->paid_at)->not->toBeNull();

    $allocation = DB::connection('pgsql_staff')->table('allocations')->where('donation_id', $donation->id)->first();
    expect($allocation)->not->toBeNull();
    expect($allocation->amount_minor)->toBe(1_500_00);

    expect(PaymentEvent::where('provider', 'finik')->where('external_event_id', 'txn-1')->exists())->toBeTrue();
});

it('rejects a webhook with an invalid signature and leaves the donation pending', function () {
    [$case, $donation] = createStagedDonation([
        'status' => 'pending',
        'provider' => 'finik',
        'provider_ref' => 'pay-bad-sig',
        'paid_at' => null,
    ]);

    $payload = ['transactionId' => 'txn-2', 'status' => 'success', 'fields' => ['paymentId' => 'pay-bad-sig']];
    $headers = signFinikWebhook($payload, $this->finikPrivateKey, $this->webhookUrl, $this->apiKey);
    $headers['signature'] = base64_encode('not a real signature');

    $response = $this->postJson('/webhooks/finik', $payload, $headers);

    $response->assertStatus(400);
    expect(Donation::on('pgsql_staff')->find($donation->id)->status)->toBe('pending');
});

it('is idempotent: redelivering the same transactionId does not double-allocate', function () {
    [$case, $donation] = createStagedDonation([
        'amount_minor' => 2_000_00,
        'status' => 'pending',
        'provider' => 'finik',
        'provider_ref' => 'pay-dup-1',
        'paid_at' => null,
    ]);

    $payload = ['transactionId' => 'txn-dup', 'status' => 'success', 'fields' => ['paymentId' => 'pay-dup-1']];
    $headers = signFinikWebhook($payload, $this->finikPrivateKey, $this->webhookUrl, $this->apiKey);

    $this->postJson('/webhooks/finik', $payload, $headers)->assertOk();
    $this->postJson('/webhooks/finik', $payload, $headers)->assertOk();

    $allocations = DB::connection('pgsql_staff')->table('allocations')->where('donation_id', $donation->id)->get();
    expect($allocations)->toHaveCount(1);

    expect(PaymentEvent::where('provider', 'finik')->where('external_event_id', 'txn-dup')->count())->toBe(1);
});
