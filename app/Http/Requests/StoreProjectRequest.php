<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

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
            'github_url' => [
                'bail',
                'required',
                'string',
                'max:255',
                'url:http,https',
                'regex:/^https?:\/\/(www\.)?github\.com\/[^\/\s]+\/[^\/\s]+(?:\.git)?\/?$/i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->repositoryIsPubliclyAccessible((string) $value)) {
                        $fail('The GitHub repository does not exist or is not publicly accessible.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'github_url.regex' => 'The GitHub link must point to a GitHub repository (owner/repository).',
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

    private function repositoryIsPubliclyAccessible(string $repositoryUrl): bool
    {
        $path = trim((string) parse_url($repositoryUrl, PHP_URL_PATH), '/');
        $segments = explode('/', $path);

        if (count($segments) < 2) {
            return false;
        }

        $owner = $segments[0];
        $repository = Str::before($segments[1], '.git');

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->get("https://api.github.com/repos/{$owner}/{$repository}");
        } catch (Throwable) {
            return false;
        }

        return $response->ok() && $response->json('private') !== true;
    }
}
