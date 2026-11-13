<?php

namespace App\Http\Requests;

use App\Support\Leaderboard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScoreRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'player_name' => ['required', 'string', 'min:2', 'max:24'],
            'score' => ['required', 'integer', 'min:0', 'max:'.Leaderboard::WINNING_SCORE],
            'difficulty' => ['required', 'string', Rule::in(Leaderboard::DIFFICULTIES)],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
        ];
    }

    /**
     * Get custom validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'player_name.required' => 'Enter a handle for the hall of fame.',
            'player_name.min' => 'Your handle needs at least 2 characters.',
            'player_name.max' => 'Your handle can be at most 24 characters.',
            'score.max' => 'A match is won at 7 points.',
            'difficulty.in' => 'Pick one of the three cabinet difficulties.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('player_name'))) {
            $this->merge([
                'player_name' => trim($this->input('player_name')),
            ]);
        }
    }
}
