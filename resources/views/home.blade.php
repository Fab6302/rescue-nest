@extends('layout')
@section('content')
  <section class="hero">
    <div class="hero-copy">
      <h1>A home for your pet community.</h1>
      <p>Meet local pets, share their stories, find adoption listings, and help missing pets get home.</p>
      <div class="toolbar"><a class="button" href="/browse/pet">Explore pet profiles</a><a class="button secondary" href="/browse/rescue">Find rescue cases</a></div>
    </div>
    @if($pets->first()?->photo)
      <figure><img src="{{ $pets->first()->photoUrl() }}" alt="{{ $pets->first()->title }}"><figcaption>{{ $pets->first()->title }}</figcaption></figure>
    @endif
  </section>
  <h2>Meet the pets</h2>
  <div class="grid">
    @forelse($pets as $entry)
      @include('partials.card')
    @empty
      <p>Pet profiles will appear here as members add them.</p>
    @endforelse
  </div>
@endsection