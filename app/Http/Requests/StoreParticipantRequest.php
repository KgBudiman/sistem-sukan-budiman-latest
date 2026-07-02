<?php

namespace App\Http\Requests;

use App\Models\Participant;
use App\Models\Sport;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreParticipantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $age = (int) $this->input('age');
        $category = Participant::categoryForAge($age);
        $isChild = $age > 0 && $category === Participant::CATEGORY_CHILD;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'phone' => [$isChild ? 'nullable' : 'required', 'string', 'max:20', 'regex:/^(\\+?6?01)[0-9]-?[0-9]{7,8}$/'],
            'house_id' => ['required', 'exists:houses,id'],
            'sport_ids' => ['required', 'array', 'min:1'],
            'sport_ids.*' => [
                'integer',
                'required',
                'distinct',
                Rule::exists('sports', 'id')->where('is_active', true),
                function (string $attribute, mixed $value, \Closure $fail) use ($category) {
                    $sport = Sport::find($value);

                    if ($sport && $category && ! $sport->compatibleWithCategory($category)) {
                        $fail('Acara yang dipilih tidak sesuai dengan kategori peserta.');
                    }
                },
            ],
            'guardian_name' => [$isChild ? 'required' : 'nullable', 'string', 'max:255'],
            'guardian_phone' => [$isChild ? 'required' : 'nullable', 'string', 'max:20', 'regex:/^(\\+?6?01)[0-9]-?[0-9]{7,8}$/'],
            'guardian_relationship' => [$isChild ? 'required' : 'nullable', 'string', 'max:100'],
            'captcha_answer' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $expectedAnswer = $this->session()->get('registration_captcha_answer');

                    if ($expectedAnswer === null || (int) $value !== (int) $expectedAnswer) {
                        $fail('Jawapan captcha tidak tepat. Sila cuba lagi.');
                    }
                },
            ],
            'consent_agreement' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $age = (int) $this->input('age');

        $this->merge([
            'name' => Str::of($this->input('name'))->squish()->toString(),
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'guardian_phone' => PhoneNumber::normalize($this->input('guardian_phone')),
            'category' => $age > 0 ? Participant::categoryForAge($age) : null,
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->session()->forget('registration_captcha_answer');

        parent::failedValidation($validator);
    }

    public function messages(): array
    {
        return [
            '*.required' => 'Medan ini wajib diisi.',
            '*.regex' => 'Sila masukkan nombor telefon Malaysia yang sah.',
            'captcha_answer.required' => 'Sila jawab soalan captcha.',
            'captcha_answer.integer' => 'Jawapan captcha mesti dalam nombor.',
            'consent_agreement.accepted' => 'Sila sahkan persetujuan sebelum menghantar pendaftaran.',
            'sport_ids.required' => 'Sila pilih sekurang-kurangnya satu acara.',
            'sport_ids.*.exists' => 'Acara yang dipilih tidak tersedia.',
            'sport_ids.*' => 'Acara yang dipilih tidak sesuai dengan kategori umur peserta.',
        ];
    }
}
