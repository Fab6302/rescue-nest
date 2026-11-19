<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'RescueNest') · Pet community</title>
  <link rel="stylesheet" href="{{ asset('site.css') }}">
</head>
<body>
  <header class="top"><div class="wrap nav">
    <a class="brand" href="/">Rescue<span>Nest</span></a>
    <nav aria-label="Main navigation">
      <a href="/browse/pet">Pets</a><a href="/browse/update">Community</a>
      <a href="/browse/adoption">Adoption</a><a href="/browse/shop">Shops</a>
      <a href="/browse/product">Products</a><a href="/browse/rescue">Rescue</a><a href="/browse/notices">Lost &amp; found</a>
    </nav>
    <div class="account-nav">
      @auth
        <a href="/saved">Saved</a><a href="/my-activity">My activity</a>
        @if(auth()->user()->role === 'moderator') <a href="/moderation">Moderation</a> @endif
        <a href="/account">Account</a>
        <form method="post" action="/logout">@csrf <button class="plain">Log out</button></form>
      @else
        <a href="/login">Log in</a><a class="button" href="/register">Join RescueNest</a>
      @endauth
    </div>
  </div></header>
  <main class="wrap main">
    @if(session('status')) <p class="feedback" role="status">{{ session('status') }}</p> @endif
    @if($errors->any())
      <div class="feedback error" role="alert"><ul>
        @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
      </ul></div>
    @endif
    @yield('content')
  </main>
  <footer class="wrap footer">RescueNest · Pet community</footer>
</body>
</html>