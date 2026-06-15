<?php

namespace App\Http\Controllers\Concerns;

use Symfony\Component\HttpFoundation\Cookie;

/**
 * Centraliza a emissão do cookie HttpOnly que carrega o JWT, para que
 * AdminAuthController (login/senha) e GithubAuthController (OAuth) usem
 * exatamente os mesmos parâmetros (nome, TTL, flags).
 */
trait IssuesTokenCookie
{
    /** Mesmo nome lido pelo App\Http\Middleware\Authenticate. */
    protected function tokenCookie(string $token): Cookie
    {
        return cookie(
            name: 'graviton_token',
            value: $token,
            minutes: (int) config('jwt.ttl'),
            path: '/',
            domain: null,
            secure: app()->environment('production'),
            httpOnly: true,
            raw: false,
            // 'lax' cobre o dev (mesmo site, portas diferentes) e o retorno do OAuth
            // (navegação top-level GET). Em produção cross-domain, trocar p/ 'none' (HTTPS).
            sameSite: 'lax',
        );
    }

    protected function forgetTokenCookie(): Cookie
    {
        return cookie()->forget('graviton_token', '/');
    }
}
