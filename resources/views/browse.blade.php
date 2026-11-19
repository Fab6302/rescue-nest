@extends('layout')
@section('title', $heading)
@section('content')
  <div class="page-heading"><h1>{{ $heading }}</h1>
    @php
      $canCreate = in_array($kind, ['pet','adoption','rescue','lost','found'], true) || ($kind === 'shop' && in_array(auth()->user()?->role, ['shop','moderator'], true));
    @endphp
    @if($canCreate) <a class="button" href="/create/{{ $kind }}">Add {{ strtolower(config('community.kinds')[$kind][1]) }}</a> @endif
    @if(in_array($kind, ['shop','product'], true))
      @guest
        <a class="button secondary" href="/register">Register as a shop owner</a>
      @else
        @if(auth()->user()->role === 'member')
          <form method="post" action="/account/shop">
            @csrf
            <button class="button">Become a shop owner</button>
          </form>
        @elseif($kind === 'product' && in_array(auth()->user()->role, ['shop','moderator'], true))
          <a class="button" href="/my-shops">Add a product</a>
        @endif
      @endguest
    @endif
    @if($kind === 'notices') <div class="toolbar"><a class="button" href="/create/lost">Report lost pet</a><a class="button secondary" href="/create/found">Report found pet</a></div> @endif
  </div>
  @if($kind === 'rescue' && auth()->user()?->role === 'rescuer')
    <p><a href="/browse/rescue{{ request('assigned') === '1' ? '' : '?assigned=1' }}">{{ request('assigned') === '1' ? 'Browse all rescue cases' : 'View my claimed cases' }}</a></p>
  @endif
  @if(in_array($kind, [...config('community.public'), 'notices'], true))
    <form class="filters panel" method="get" action="{{ url()->current() }}">
      @if($kind === 'rescue' && request('assigned') === '1')
        <input type="hidden" name="assigned" value="1">
      @endif
      <label>Search<input name="q" maxlength="120" value="{{ request('q') }}"></label>
      @php
        $choices = [];
        if (in_array($kind, ['pet','adoption','shop','product','rescue','notices','lost','found'])) $choices['area'] = array_combine(config('community.areas'), config('community.areas'));
        if (in_array($kind, ['pet','adoption','rescue','notices','lost','found'])) $choices['animal'] = array_combine(config('community.animals'), config('community.animals'));
        if ($kind === 'adoption') {
          $choices['age_group'] = ['young' => 'Under 1 year','adult' => '1 year or older'];
          $choices['status'] = ['all' => 'All statuses','adopted' => 'Adopted','withdrawn' => 'Withdrawn'];
        }
        if ($kind === 'rescue') {
          $choices['status'] = ['all' => 'All statuses','in_progress' => 'In progress','rescued' => 'Rescued','closed' => 'Closed'];
          $choices['urgency'] = ['routine' => 'Routine','urgent' => 'Urgent'];
        }
        if ($kind === 'product') {
          $choices['category'] = array_combine(config('community.categories'), config('community.categories'));
          $choices['availability'] = ['available' => 'In stock','unavailable' => 'Unavailable'];
        }
        if (in_array($kind, ['notices','lost','found'])) $choices['status'] = ['all' => 'All statuses','reunited' => 'Reunited','closed' => 'Closed'];
        if ($kind === 'notices') $choices['notice'] = ['lost' => 'Lost','found' => 'Found'];
      @endphp
      @foreach($choices as $field => $values)
        <label>{{ ucfirst(str_replace('_',' ',$field)) }}
          <select name="{{ $field }}"><option value="">{{ $field === 'status' ? ($kind === 'adoption' ? 'Available only' : 'Open only') : 'Any' }}</option>
            @foreach($values as $value => $label)<option value="{{ $value }}" @selected((request($field) ?? ($kind === 'rescue' && $field === 'status' && request('assigned') === '1' ? 'all' : null)) === $value)>{{ $label }}</option>@endforeach
          </select>
        </label>
      @endforeach
      @if($kind === 'pet')<label>Breed<input name="breed" maxlength="100" value="{{ request('breed') }}"></label>@endif
      @if($kind === 'product')
        <label>Minimum price<input type="number" min="0" step="0.01" name="min" value="{{ request('min') }}"></label>
        <label>Maximum price<input type="number" min="0" step="0.01" name="max" value="{{ request('max') }}"></label>
      @endif
      @if(in_array($kind, ['notices','lost','found']))<label>Notice date<input type="date" name="date" value="{{ request('date') }}"></label>@endif
      <div class="toolbar"><button class="button">Search</button><a href="{{ url()->current() }}">Clear</a></div>
    </form>
  @endif
  @if($kind === 'update')
    @include('partials.members')
  @endif
  <div class="grid">
    @forelse($entries as $entry)
      @include('partials.card')
    @empty
      <p class="empty">No matching entries.</p>
    @endforelse
  </div>
  @include('partials.pager', ['items' => $entries])
@endsection