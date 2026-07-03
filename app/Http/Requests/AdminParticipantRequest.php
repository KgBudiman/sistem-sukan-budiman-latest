<?php

namespace App\Http\Requests;

use App\Models\Participant;
use App\Models\Sport;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminParticipantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $participantId = $this->route('participant')?->id;
        $age = (int) $this->input('age');
        $category = Participant::categoryForAge($age);
        $isChild = $age > 0 && $category === Participant::CATEGORY_CHILD;

        return [
            'name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'phone' => [
                $isChild ? 'nullable' : 'required',
                'string',
                'max:20',
                'regex:/^(\\+?6?01)[0-9]-?[0-9]{7,8}$/',
                Rule::unique('participants')
                    ->where(fn ($query) => $query->where('name', $this->input('name')))
                    ->ignore($participantId),
            ],
            'house_id' => ['required', 'exists:houses,id'],
            'status' => ['required', Rule::in(['Aktif', 'Dibatalkan'])],
            'sport_ids' => ['nullable', 'array'],
            'sport_ids.*' => [
                'integer',
                'distinct',
                'exists:sports,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($category) {
                    $sport = Sport::find($value);

                    if ($sport && $category && ! $sport->compatibleWithCategory($category)) {
                        $fail('Acara yang dipilih tidak sesuai dengan kategori peserta.');
                    }
                },
            ],
            'sport_statuses' => ['nullable', 'array'],
            'sport_statuses.*' => ['nullable', Rule::in(['Menunggu', 'Diterima', 'Ditolak', 'Senarai Menunggu', 'Dibatalkan'])],
            'notes' => ['nullable', 'string'],
            'guardian_name' => [$isChild ? 'required' : 'nullable', 'string', 'max:255'],
            'guardian_phone' => [$isChild ? 'required' : 'nullable', 'string', 'max:20', 'regex:/^(\\+?6?01)[0-9]-?[0-9]{7,8}$/'],
            'guardian_relationship' => [$isChild ? 'required' : 'nullable', 'string', 'max:100'],
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

    public function messages(): array
    {
        return [
            '*.required' => 'Medan ini wajib diisi.',
            '*.regex' => 'Sila masukkan nombor telefon Malaysia yang sah.',
            'phone.unique' => 'Rekod peserta dengan nama dan nombor telefon ini telah wujud.',
            'sport_ids.*' => 'Acara yang dipilih tidak sesuai dengan kategori umur peserta.',
        ];
    }
}
