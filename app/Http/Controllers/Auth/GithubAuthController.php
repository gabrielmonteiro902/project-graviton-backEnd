<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\IssuesTokenCookie;
use App\Http\Controllers\Controller;
use App\Jobs\ImportGithubReposJob;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GithubAuthController extends Controller
{
    use IssuesTokenCookie;

    public function __construct(private readonly AuthService $authService) {}

    /**
     * Manda o usuário para a tela de autorização do GitHub.
     * Escopo mínimo (read:user, user:email) — só repositórios públicos.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('github_oauth_state', $state);

        $params = http_build_query([
            'client_id'    => config('services.github.client_id'),
            'redirect_uri' => config('services.github.redirect'),
            'scope'        => 'read:user user:email',
            'state'        => $state,
            'allow_signup' => 'false',
        ]);

        return redirect('https://github.com/login/oauth/authorize?' . $params);
    }

    public function callback(Request $request): RedirectResponse
    {
        $frontend = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');

        // Anti-CSRF: o state retornado precisa bater com o que guardamos na sessão.
        $state = $request->session()->pull('github_oauth_state');
        if (! $state || ! hash_equals($state, (string) $request->query('state'))) {
            return redirect($frontend . '/?error=github_state');
        }

        if (! $request->filled('code')) {
            return redirect($frontend . '/?error=github_cancelled');
        }

        try {
            $accessToken = $this->exchangeCodeForToken((string) $request->query('code'));
            if (! $accessToken) {
                return redirect($frontend . '/?error=github_token');
            }

            $ghUser = Http::withToken($accessToken)->acceptJson()
                ->get('https://api.github.com/user')->json();

            if (! is_array($ghUser) || empty($ghUser['id'])) {
                return redirect($frontend . '/?error=github_profile');
            }

            $email = $this->resolveEmail($ghUser, $accessToken);
            $admin = $this->authService->findOrCreateFromGithub($ghUser, $email, $accessToken);

            // Mesma sessão do login por senha: JWT em cookie HttpOnly.
            $token = auth('admin')->login($admin);

            // Importa os repositórios PÚBLICOS do usuário em background.
            ImportGithubReposJob::dispatch($admin->id, $admin->tenant_id);

            return redirect($frontend . '/auth/callback')
                ->withCookie($this->tokenCookie($token));

        } catch (Throwable $e) {
            Log::error('GitHub OAuth callback falhou', ['error' => $e->getMessage()]);

            return redirect($frontend . '/?error=github_failed');
        }
    }

    private function exchangeCodeForToken(string $code): ?string
    {
        $response = Http::acceptJson()->asForm()
            ->post('https://github.com/login/oauth/access_token', [
                'client_id'     => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'code'          => $code,
                'redirect_uri'  => config('services.github.redirect'),
            ]);

        return $response->json('access_token');
    }

    private function resolveEmail(array $ghUser, string $accessToken): string
    {
        if (! empty($ghUser['email'])) {
            return $ghUser['email'];
        }

        $emails = Http::withToken($accessToken)->acceptJson()
            ->get('https://api.github.com/user/emails')->json();

        if (is_array($emails)) {
            $primary = collect($emails)->firstWhere('primary', true);
            if ($primary && ! empty($primary['email'])) {
                return $primary['email'];
            }
        }

        // Fallback: e-mail noreply do GitHub (usuário sem e-mail público).
        return ($ghUser['login'] ?? 'user') . '@users.noreply.github.com';
    }
}
