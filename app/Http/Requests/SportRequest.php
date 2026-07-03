<?php

namespace App\Http\Requests;

use App\Models\Participant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SportRequest extends FormRequest
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
        $sportId = $this->route('sport')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('sports')->ignore($sportId)],
            'category' => ['required', Rule::in(Participant::SPORT_CATEGORIES)],
            'max_players_per_group' => ['nullable', 'integer', 'min:1', 'max:999'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:999'],
            'group_code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'rules' => ['nullable', 'string'],
            'equipment' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
