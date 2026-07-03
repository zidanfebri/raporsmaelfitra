<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SiakadController extends Controller
{
    public function index() {
        // Jika user sudah login, arahkan langsung ke dashboard
        if (Auth::check()) {
            return redirect()->intended('dashboard');
        }
        return view('auth.login');
    }

    public function postLogin(Request $request) {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // 1. Cek dulu apakah user tersebut ada dan statusnya Aktif (1)
        $user = User::where('username', $request->username)->first();

        if ($user && $user->status == 0) {
            // Jika user ditemukan tapi statusnya 0 (Non-Aktif), gagalkan login
            return back()->with('error', 'Akun Anda dinonaktifkan. Silakan hubungi admin!');
        }

        // 2. Jika status aktif atau user admin (status 1), lakukan attempt login biasa
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password, 'status' => 1])) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        // 3. Jika gagal karena password salah atau username tidak ada
        return back()->with('error', 'Username atau password salah!');
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}   