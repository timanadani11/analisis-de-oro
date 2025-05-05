<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminAuthController extends Controller
{
    /**
     * Mostrar formulario de login para el administrador
     */
    public function loginForm()
    {
        return Inertia::render('Auth/Login', [
            'isAdmin' => true
        ]);
    }

    /**
     * Autenticar al administrador
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Verificar si el usuario es administrador
            if ($user->role === 'admin') {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            }
            
            // Si no es administrador, cerrar sesión y mostrar error
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return back()->withErrors([
                'email' => 'No tienes permisos de administrador.',
            ]);
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no son válidas.',
        ]);
    }

    /**
     * Cerrar sesión del administrador
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Logged out successfully']);
        }

        return redirect('/')->with('status', 'Logged out successfully');
    }
}
