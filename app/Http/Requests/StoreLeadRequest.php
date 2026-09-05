<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],

            // Um dos dois chega. Nem toda a gente quer dar o email, e neste
            // setor quase toda a gente prefere que lhe liguem.
            'email' => ['nullable', 'required_without:phone', 'email:filter', 'max:180'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:32', 'regex:/^[0-9+\s().-]{6,}$/'],

            'event_type' => ['required', Rule::in(EventType::values())],
            'event_date' => ['nullable', 'date', 'after_or_equal:today', 'before:+3 years'],
            'guests_count' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'venue' => ['nullable', 'string', 'max:200'],
            'message' => ['nullable', 'string', 'max:2000'],

            // Consentimento explícito, sem pré-marcar. O RGPD exige que
            // seja um ato afirmativo — uma caixa já marcada não vale.
            'privacy' => ['accepted'],

            // Armadilha para robots: um campo escondido que uma pessoa
            // nunca preenche. Custa zero e apanha a maior parte do spam
            // automático sem chatear ninguém com captchas.
            'website' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return __('forms.attributes');
    }

    public function messages(): array
    {
        return [
            'email.required_without' => __('forms.errors.contact_required'),
            'phone.required_without' => __('forms.errors.contact_required'),
            'phone.regex' => __('forms.errors.phone_format'),
            'privacy.accepted' => __('forms.errors.privacy'),
            'event_date.after_or_equal' => __('forms.errors.date_past'),
            'website.prohibited' => __('forms.errors.spam'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'phone' => $this->filled('phone') ? preg_replace('/\s+/', ' ', trim((string) $this->input('phone'))) : null,
        ]);
    }
}
