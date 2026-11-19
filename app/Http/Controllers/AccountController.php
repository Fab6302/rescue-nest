<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController
{
    public function form(string $mode)
    {
        return view('account', compact('mode'));
    }

    public function resetForm(Request $request, string $token)
    {
        return view('account', ['mode' => 'reset-password', 'token' => $token, 'email' => $request->query('email')]);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:72']);
        $data['email'] = Str::lower($data['email']);
        if (!Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    public function register(Request $request)
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:72|confirmed',
            'role' => 'nullable|in:member,rescuer,shop',
        ]);
        $user = new User(collect($data)->except('role')->all());
        $user->role = $data['role'] ?? 'member';
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();
        return redirect('/');
    }

    public function forgot(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        Password::sendResetLink(['email' => Str::lower($data['email'])]);
        return back()->with('status', 'If an account exists for that email, a reset link was requested.');
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string', 'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:72|confirmed',
        ]);
        $data['email'] = Str::lower($data['email']);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => 'required|current_password',
            'password' => 'nullable|string|min:8|max:72|confirmed',
        ]);
        $user->fill(array_filter(collect($data)->only(['name', 'email', 'password'])->all()))->save();
        $request->session()->regenerate();
        return back()->with('status', 'Account updated.');
    }

    public function enableShop(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'member') {
            User::whereKey($user->id)->where('role', 'member')->update(['role' => 'shop']);
        }
        abort_unless($user->refresh()->role === 'shop', 403);

        return redirect('/create/shop')->with('status', 'Shop tools are enabled. Create your first shop below.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}