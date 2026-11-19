@extends('layout')
@section('title', $entry->title)
@section('content')
  @php
    $user = auth()->user();
    $owner = $user && $entry->user_id === $user->id;
    $parentOwner = $user && $entry->parent?->user_id === $user->id;
    $role = $user?->role;
    $open = $entry->status === 'open';
    $active = in_array($entry->status, ['open', 'question', 'accepted'], true);
    $canEdit = app(\App\Http\Controllers\CommunityController::class)->canEdit($entry, $user);
    $create = match($entry->kind) {
      'pet' => $owner && $open ? ['update', 'Share an update'] : null,
      'update' => ['comment', 'Add a comment'],
      'comment' => ['comment', 'Reply'],
      'adoption' => $open && !$owner ? ['application', 'Request adoption'] : null,
      'shop' => $owner ? ['product', 'Add a product'] : ['review', 'Write a review'],
      'lost' => $open && !$owner ? ['sighting', 'Report a sighting'] : null,
      'application', 'sighting' => in_array($entry->status, ['open','question','reviewed','accepted']) ? ['question', $parentOwner ? 'Ask a question' : 'Send a reply'] : null,
      default => null,
    };
    $actions = [];
    if ($user && in_array($entry->kind, config('community.saveable'))) $actions[$isSaved ? 'unsave' : 'save'] = $isSaved ? 'Remove from saved' : 'Save';
    if ($owner && $open && in_array($entry->kind, ['update','comment','review','product'])) $actions['delete'] = 'Remove '.strtolower(config('community.kinds')[$entry->kind][1]);
    if ($owner && $active && in_array($entry->kind, ['adoption','application'])) $actions['withdraw'] = 'Withdraw';
    if ($entry->kind === 'rescue') {
        if ($role === 'rescuer' && $open && !$owner) $actions['claim'] = 'Claim rescue case';
        if ($role === 'rescuer' && $entry->status === 'in_progress' && ($entry->details['rescuer_id'] ?? null) === $user->id) $actions['resolve'] = 'Mark rescued';
        if ($owner && $open) $actions['close'] = 'Close case';
    }
    if ($owner && $open && $entry->kind === 'lost') $actions['reunite'] = 'Mark reunited';
    if ($owner && $open && $entry->kind === 'found') $actions['close'] = 'Close notice';
    if ($parentOwner && $active && $entry->kind === 'application' && $entry->parent?->status === 'open') {
        if ($entry->status === 'accepted') $actions['complete'] = 'Confirm handover';
        else $actions['accept'] = 'Accept request';
        $actions['decline'] = 'Decline request';
    }
    if ($parentOwner && in_array($entry->status, ['open','question']) && $entry->kind === 'sighting') $actions['review'] = 'Mark reviewed';
    if ($role === 'moderator' && $entry->kind === 'report' && $open) {
        $actions['dismiss'] = 'Dismiss report';
        $actions['remove'] = 'Remove reported content';
    }
    $detailLabels = ['weight'=>'Weight (kg)', 'diet'=>'Food habits', 'favorites'=>'Favorite foods', 'secondary_area'=>'Also listed under', 'age'=>'Age in years', 'date'=>$entry->kind === 'lost' ? 'Last-seen date' : 'Date', 'care'=>$entry->kind === 'application' ? 'Care plan' : 'Care needs'];
  @endphp
  @if($entry->parent && !in_array($entry->parent->status, ['removed','deleted']))
    <p><a href="/entry/{{ $entry->parent_id }}">← {{ $entry->parent->title }}</a></p>
  @endif
  <article class="panel detail">
    @if($entry->photo)<img class="detail-photo" src="{{ $entry->photoUrl() }}" alt="{{ $entry->title }}">@endif
    <div>
      <p class="tag">{{ config('community.kinds')[$entry->kind][1] }} · {{ ucfirst(str_replace('_', ' ', $entry->status)) }}</p>
      <h1>{{ $entry->title }}</h1>
      <p class="muted">{{ $entry->author->name }} · {{ $entry->created_at->format('M j, Y') }}</p>
      @if($rating !== null)<p>Average rating <strong>{{ number_format($rating, 1) }} / 5</strong></p>@endif
      <p class="body-text">{{ $entry->body }}</p>
      <dl>
        @foreach(array_filter(['animal'=>$entry->animal, 'area'=>$entry->area], fn ($value) => $value !== null) + array_filter(collect($entry->details ?? [])->except('rescuer_id')->all(), fn ($value) => $value !== null && $value !== '') as $key => $value)
          <dt>{{ $detailLabels[$key] ?? ucfirst($key) }}</dt><dd>{{ $key === 'price' ? '৳' : '' }}{{ $value }}</dd>
        @endforeach
      </dl>
      <div class="toolbar">
        @if($canEdit)<a class="button secondary" href="/edit/{{ $entry->id }}">Edit</a>@endif
        @if($create && ($user || in_array($entry->kind, ['update','adoption','shop','lost'])))
          <a class="button" href="/create/{{ $create[0] }}/{{ $entry->id }}">{{ $create[1] }}</a>
        @endif
        @foreach($actions as $action => $label)
          @include('partials.action', ['target' => $entry, 'action' => $action, 'label' => $label])
        @endforeach
        @if($user && in_array($entry->kind, config('community.reportable')))
          <a href="/create/report/{{ $entry->id }}">Report content</a>
        @endif
      </div>
    </div>
  </article>
  @if($children->isNotEmpty())
    <h2>{{ ['pet'=>'Pet updates', 'update'=>'Comments', 'comment'=>'Replies', 'shop'=>'Products and reviews', 'adoption'=>'Adoption requests', 'application'=>'Conversation', 'lost'=>'Sightings', 'sighting'=>'Conversation'][$entry->kind] ?? 'Related entries' }}</h2>
    <div class="grid">
      @foreach($children as $child)
        @include('partials.card', ['entry' => $child])
      @endforeach
    </div>
    @include('partials.pager', ['items' => $children])
  @endif
@endsection