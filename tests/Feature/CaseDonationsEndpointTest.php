<?php

/**
 * /cases/{case}/donations — JSON, вызывается из DonationsModal.vue при
 * открытии модалки "Донаты" (вкладки "Все"/"Топ"). Реальную сортировку
 * (топ по убыванию суммы) не тестируем здесь через фабрику: FundCase,
 * созданный в тесте, не виден со стороны pgsql_public до коммита —
 * то же самое ограничение тестового стенда, что и в
 * PublicDonationFlowTest/NavCasesSharedPropTest (там же — почему
 * CaseController в принципе не покрыт автотестами на happy path).
 * Проверено вручную в браузере на реальных донатах.
 */
it('404s for a nonexistent case regardless of sort param', function () {
    $this->get('/cases/999999/donations')->assertNotFound();
    $this->get('/cases/999999/donations?sort=top')->assertNotFound();
});
