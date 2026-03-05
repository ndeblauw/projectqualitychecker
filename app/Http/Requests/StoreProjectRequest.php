<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreProjectRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'github_url' => ['required', 'string', 'max:255', 'url:http,https', 'regex:/^https?:\/\/(www\.)?github\.com\/.+$/i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'github_url.regex' => 'The GitHub link must point to github.com.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $githubUrl = (string) $this->input('github_url');

        if ($githubUrl !== '' && ! Str::startsWith($githubUrl, ['http://', 'https://'])) {
            $this->merge([
                'github_url' => "https://{$githubUrl}",
            ]);
        }
    }
}
