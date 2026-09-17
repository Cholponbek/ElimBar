<?php

namespace App\Http\Middleware;

use App\Models\PublicCase;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Список кейсов для выпадающего меню "Поддержать" в хедере
            // (PublicLayout.vue) — нужен на каждой странице (кейса, формы
            // заявки и т.д.), не только на витрине, поэтому здесь, а не в
            // отдельном контроллере. Только id+заголовок — меню показывает
            // список для выбора, тащить фото/суммы каждого кейса незачем.
            'navCases' => fn () => PublicCase::query()
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id', 'public_title'])
                ->map(fn (PublicCase $case) => [
                    'id' => $case->id,
                    'title' => $case->public_title,
                ]),
        ];
    }
}
