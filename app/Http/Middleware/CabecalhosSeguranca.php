<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos HTTP de proteção: CSP com nonce (bloqueia scripts injetados),
 * anti-clickjacking, anti-sniffing, HSTS em HTTPS e "no-store" para páginas autenticadas.
 */
class CabecalhosSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        // Só têm efeito (e só são aceitos pelo navegador) em HTTPS.
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        }

        // Dados financeiros não devem ficar em cache do navegador ou de proxies.
        if (Auth::check()) {
            $headers->set('Cache-Control', 'no-store, private');
            $headers->set('Pragma', 'no-cache');
        }

        // A tela de erro detalhada do modo debug usa estilos inline; a CSP fica só para o restante.
        if (! (config('app.debug') && $response->getStatusCode() >= 500)) {
            $headers->set('Content-Security-Policy', $this->politica($request));
        }

        return $response;
    }

    private function politica(Request $request): string
    {
        $nonce = Vite::cspNonce();
        $vite = $this->servidorVite();

        $diretivas = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", $vite],
            // Só no "npm run dev" (local) o Vite injeta <style> via JS; em produção, apenas nonce.
            'style-src' => $vite ? ["'self'", "'unsafe-inline'", $vite] : ["'self'", "'nonce-{$nonce}'"],
            'img-src' => ["'self'", 'data:', $vite],
            'font-src' => ["'self'", $vite],
            'connect-src' => ["'self'", $vite, $vite ? preg_replace('/^http/', 'ws', $vite) : null],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        $politica = collect($diretivas)
            ->map(fn (array $fontes, string $nome) => $nome.' '.implode(' ', array_filter($fontes)))
            ->implode('; ');

        return $request->isSecure() ? $politica.'; upgrade-insecure-requests' : $politica;
    }

    /** Durante o desenvolvimento com "npm run dev", libera o servidor do Vite. */
    private function servidorVite(): ?string
    {
        if (! app()->isLocal() || ! is_file($arquivo = public_path('hot'))) {
            return null;
        }

        $url = trim((string) file_get_contents($arquivo));

        return preg_match('#^https?://[\w.\-\[\]:]+$#', $url) ? $url : null;
    }
}
