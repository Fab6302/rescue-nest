@extends('layout')
@php
  $headings = ['login' => 'Welcome back', 'register' => 'Join RescueNest', 'account' => 'Your account', 'forgot-password' => 'Forgot your password?', 'reset-password' => 'Choose a new password'];
@endphp
@section('title', $headings[$mode])
@section('content')
  <section class="panel narrow">
    <h1>{{ $headings[$mode] }}</h1>
    @if($mode === 'login') <p>Log in to post, comment, and manage your listings.</p> @endif
    @if($mode === 'forgot-password') <p>Enter your account email to request a password reset link.</p> @endif
    <form method="post" action="/{{ $mode }}">
      @csrf
      @if($mode === 'reset-password') <input type="hidden" name="token" value="{{ $token }}"> @endif
      @if(in_array($mode, ['register', 'account']))
        <label>Name<input name="name" required maxlength="80" autocomplete="name" value="{{ old('name', auth()->user()?->name) }}"></label>
      @endif
      <label>Email<input type="email" name="email" required autocomplete="email" value="{{ old('email', $email ?? auth()->user()?->email) }}"></label>
      @if($mode === 'account')
        <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
      @endif
      @if($mode !== 'forgot-password')
        <label>{{ $mode === 'account' ? 'New password (optional)' : 'Password' }}
          <input type="password" name="password" maxlength="72" autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}" @required($mode !== 'account') @if($mode !== 'login') minlength="8" @endif>
        </label>
      @endif
      @if(in_array($mode, ['register', 'account', 'reset-password']))
        <label>Confirm {{ $mode === 'account' ? 'new ' : '' }}password<input type="password" name="password_confirmation" autocomplete="new-password" @required($mode !== 'account')></label>
      @endif
      @if($mode === 'register')
        <label>Account type
          <select name="role">
            <option value="member" @selected(old('role', 'member') === 'member')>Cat parent / community member</option>
            <option value="rescuer" @selected(old('role') === 'rescuer')>Rescuer</option>
            <option value="shop" @selected(old('role') === 'shop')>Shop owner</option>
          </select>
        </label>
      @endif
      @if($mode === 'account') <p>Account type <strong>{{ ['member'=>'Cat parent / community member','rescuer'=>'Rescuer','shop'=>'Shop owner','moderator'=>'Moderator'][auth()->user()->role] ?? 'Member' }}</strong></p> @endif
      @if($mode === 'login') <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label> @endif
      <button class="button">{{ ['login' => 'Log in', 'register' => 'Create account', 'account' => 'Save changes', 'forgot-password' => 'Request reset link', 'reset-password' => 'Reset password'][$mode] }}</button>
    </form>
    @if($mode === 'login') <p><a href="/forgot-password">Forgot password?</a> · <a href="/register">Create an account</a></p> @endif
    @if(in_array($mode, ['register', 'forgot-password', 'reset-password'])) <p><a href="/login">Back to login</a></p> @endif
  </section>
@endsection