<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];

        if ($this->isRecaptchaEnabled()) {
            $rules['captcha_token'] = ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if (! $this->verifyRecaptchaToken((string) $value)) {
                    $fail('Captcha verification failed. Please try again.');
                }
            }];
        }

        return $rules;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    protected function isRecaptchaEnabled(): bool
    {
        return (bool) config('services.recaptcha.enabled')
            && ! empty(config('services.recaptcha.site_key'))
            && ! empty(config('services.recaptcha.secret_key'));
    }

    protected function verifyRecaptchaToken(string $token): bool
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $token,
            'remoteip' => $this->ip(),
        ]);

        if (! $response->ok()) {
            return false;
        }

        $result = $response->json();
        $minScore = (float) config('services.recaptcha.min_score', 0.5);

        if (! ($result['success'] ?? false)) {
            return false;
        }

        if ($this->recaptchaVersion() === 'v3') {
            if (isset($result['score']) && (float) $result['score'] < $minScore) {
                return false;
            }

            if (isset($result['action']) && $result['action'] !== 'login') {
                return false;
            }
        }

        return true;
    }

    protected function recaptchaVersion(): string
    {
        return (string) config('services.recaptcha.version', 'v2');
    }
}
