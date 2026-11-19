@extends('layout')
@section('title', 'Choose a shop')
@section('content')
  <h1>Choose a shop for your product</h1>
  <div class="grid">
    @forelse($shops as $shop)
      <article class="panel">
        <h2>{{ $shop->title }}</h2>
        @if($shop->area)<p>{{ $shop->area }}</p>@endif
        <a class="button" href="/create/product/{{ $shop->id }}">Add a product</a>
      </article>
    @empty
      <div class="panel">
        <p>Create a shop profile before adding your first product.</p>
        <a class="button" href="/create/shop">Add shop profile</a>
      </div>
    @endforelse
  </div>
  @include('partials.pager', ['items' => $shops])
@endsection