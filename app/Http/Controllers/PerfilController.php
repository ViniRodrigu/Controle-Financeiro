<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function show(Request $request): View
    {
        return view('perfil.index', ['usuario' => $request->user()]);
    }

    /** Gestão do 2FA. A rota exige confirmação recente de senha (middleware password.confirm). */
    public function seguranca(Request $request): View
    {
        return view('perfil.seguranca', ['usuario' => $request->user()]);
    }
}
