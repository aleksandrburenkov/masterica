<?php

namespace App\Http\Controllers;

use App\Models\DistanceTariff;
use App\Models\Masterclass;
use App\Services\OutreachService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OutreachController extends Controller
{
    public function __construct(
        private readonly OutreachService $service,
    ) {}

    public function index(): View
    {
        $masterclasses = Masterclass::query()->published()->orderBy('sort')->get();

        return view('pages.outreach', [
            'masterclasses' => $masterclasses,
            'distanceTariffs' => DistanceTariff::frontendRows(),
            'office' => $this->officeCoordinates(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'string', 'max:160'],
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'location' => ['required', 'string', 'max:200'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:20000'],
            'participants_count' => ['required', 'integer', 'min:1', 'max:60'],
            'age_group' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['required', 'email', 'max:160'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'consent_data' => ['accepted'],
            'consent_marketing' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $outreach = $this->service->create($request->user(), $data);

        $message = 'Заявка '.$outreach->number.' принята. Расчётная стоимость: '.money($outreach->calc_total_cents).'. Мы свяжемся для подтверждения.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'number' => $outreach->number,
            ], 201);
        }

        return redirect()->to($this->redirectTarget($request))->with('success', $message);
    }

    /**
     * Координаты офиса из «Настроек студии». Если они не заданы, фронтенд
     * не сможет рассчитать расстояние и оставит наценку нулевой.
     *
     * @return array{lat:float,lng:float}|null
     */
    private function officeCoordinates(): ?array
    {
        $lat = setting('studio.latitude');
        $lng = setting('studio.longitude');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Возвращаем пользователя на страницу, где была заполнена форма, чтобы
     * подтверждение отобразилось на месте, а не на странице выездных занятий.
     */
    private function redirectTarget(Request $request): string
    {
        $source = trim((string) $request->input('source'));

        if ($source !== '' && ! str_contains($source, '://') && ! str_starts_with($source, '//')) {
            return url($source);
        }

        return route('outreach.index');
    }
}
