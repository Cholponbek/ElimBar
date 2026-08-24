<?php

return [
    /*
     * Фаза 1: единственный тенант фонда «Элим, барсыңбы?!». Фаза 3
     * (мультифонд) заменит это резолвингом по домену — см.
     * App\Http\Middleware\SetTenantContext.
     */
    'id' => env('TENANT_ID', 1),
];
