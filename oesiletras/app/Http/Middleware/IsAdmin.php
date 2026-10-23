<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return redirect('/')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        // Una cuenta desactivada pierde el acceso de inmediato, incluso con la
        // sesión ya abierta. Sin esta comprobación, dar de baja a un usuario no
        // surtía efecto hasta que cerrara sesión por su cuenta.
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('error', 'Tu cuenta ha sido desactivada.');
        }

        return $next($request);
    }
}
