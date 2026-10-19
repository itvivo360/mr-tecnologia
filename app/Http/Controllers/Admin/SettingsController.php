<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /** Íconos ofrecidos para los servicios (nombres de @element-plus/icons-vue). */
    public const SERVICE_ICONS = [
        'Monitor', 'Cpu', 'Iphone', 'Connection', 'Tools', 'Calendar', 'Headset', 'Box',
        'Van', 'Lock', 'VideoCamera', 'Printer', 'Setting', 'Service', 'Goods', 'House',
    ];

    public function __construct(private readonly Settings $settings)
    {
    }

    public function index(Request $request): Response
    {
        $tab = in_array($request->query('tab'), Settings::SECTIONS, true) ? $request->query('tab') : 'site';

        return Inertia::render('Admin/Settings', [
            'tab' => $tab,
            'values' => collect(Settings::SECTIONS)->mapWithKeys(fn ($s) => [$s => $this->settings->section($s)]),
            // Secciones ya guardadas desde aquí (las demás muestran los valores iniciales del .env).
            'customized' => Setting::query()->get(['key', 'updated_at'])
                ->mapWithKeys(fn (Setting $s) => [$s->key => $s->updated_at->toIso8601String()]),
            'networks' => collect(Settings::NETWORKS)->map(fn ($label, $key) => compact('key', 'label'))->values(),
            'icons' => self::SERVICE_ICONS,
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        abort_unless(in_array($section, Settings::SECTIONS, true), 404);

        $data = match ($section) {
            'site' => $request->validate([
                'name' => ['required', 'string', 'max:80'],
                'tagline' => ['required', 'string', 'max:160'],
                'description' => ['required', 'string', 'max:400'],
            ]),
            'contact' => $request->validate([
                'whatsapp' => ['nullable', 'string', 'regex:/^\+?[0-9 ]{8,20}$/'],
                'whatsapp_message' => ['nullable', 'string', 'max:200'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:190'],
                'address' => ['nullable', 'string', 'max:200'],
                'maps_url' => ['nullable', 'url:https', 'max:500'],
                'hours' => ['nullable', 'string', 'max:160'],
            ], ['whatsapp.regex' => 'Usa solo números con código de país, por ejemplo 56912345678.']),
            'social' => $request->validate(
                collect(Settings::NETWORKS)->mapWithKeys(fn ($l, $key) => [$key => ['nullable', 'url:https', 'max:300']])->all()
            ),
            'services' => $request->validate([
                'items' => ['present', 'array', 'max:8'],
                'items.*.title' => ['required', 'string', 'max:80'],
                'items.*.text' => ['required', 'string', 'max:300'],
                'items.*.icon' => ['required', Rule::in(self::SERVICE_ICONS)],
                'items.*.action' => ['required', Rule::in(['shop', 'support', 'whatsapp', 'none'])],
            ])['items'],
            'support' => $this->validateSupport($request),
        };

        if ($section === 'contact' && filled($data['whatsapp'] ?? null)) {
            $data['whatsapp'] = preg_replace('/\D/', '', $data['whatsapp']);
        }

        $this->settings->put($section, $data, $request->user());

        return redirect()->route('admin.settings.index', ['tab' => $section])->with('success', 'Configuración guardada.');
    }

    private function validateSupport(Request $request): array
    {
        $validator = validator($request->all(), [
            'open_hour' => ['required', 'integer', 'between:0,23'],
            'close_hour' => ['required', 'integer', 'between:1,24', 'gt:open_hour'],
            'slot_minutes' => ['required', 'integer', Rule::in([15, 20, 30, 45, 60, 90, 120])],
            'slot_capacity' => ['required', 'integer', 'between:1,20'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:1,7', 'distinct'],
            'booking_horizon_days' => ['required', 'integer', 'between:1,180'],
            'min_notice_hours' => ['required', 'integer', 'between:0,168'],
        ], [
            'close_hour.gt' => 'La hora de cierre debe ser posterior a la de apertura.',
            'working_days.required' => 'Elige al menos un día de atención.',
        ]);

        $validator->after(function (Validator $v) use ($request) {
            $minutes = ((int) $request->input('close_hour') - (int) $request->input('open_hour')) * 60;
            if ($minutes > 0 && (int) $request->input('slot_minutes') > $minutes) {
                $v->errors()->add('slot_minutes', 'La duración del turno no cabe en el horario de atención.');
            }
        });

        $data = $validator->validate();
        $data['working_days'] = collect($data['working_days'])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();

        return array_map(fn ($v) => is_numeric($v) ? (int) $v : $v, $data);
    }
}
