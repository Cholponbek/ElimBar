<?php

/**
 * navCases — выпадающее меню "Поддержать" в хедере (PublicLayout.vue),
 * общее для всех публичных страниц (см. HandleInertiaRequests::share()).
 *
 * Не создаём здесь FundCase через factory и не проверяем, что он реально
 * попадает в navCases: pgsql_public — отдельная физическая сессия
 * Postgres, а RefreshDatabase оборачивает в транзакцию только дефолтное
 * подключение, так что созданный в тесте кейс не виден со стороны
 * pgsql_public до коммита — то же самое ограничение тестового стенда,
 * что описано в PublicDonationFlowTest. Реальное содержимое меню (кейс
 * появляется/пропадает по статусу) проверено вручную в браузере.
 */
it('shares navCases on the homepage', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('navCases'));
});

it('shares navCases on an unrelated public page too, not just the case index', function () {
    $response = $this->get('/help');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('navCases'));
});
