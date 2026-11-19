<article class="card">
  <a href="/entry/{{ $entry->id }}">
    @if($entry->photo)<img loading="lazy" src="{{ $entry->photoUrl() }}" alt="{{ $entry->title }}">@endif
    <div class="card-copy">
      <span class="tag">{{ config('community.kinds')[$entry->kind][1] }} @if($entry->status !== 'open') · {{ ucfirst(str_replace('_', ' ', $entry->status)) }} @endif</span>
      <h3>{{ $entry->title }}</h3>
      @if($entry->area || $entry->animal)<p>{{ implode(' · ', array_filter([$entry->animal, $entry->area])) }}</p>@endif
      @if($entry->kind === 'product')<p>৳{{ $entry->details['price'] }} · {{ ucfirst($entry->details['availability']) }} · {{ $entry->parent?->title }}</p>@endif
      @if($entry->kind === 'update')<p>{{ $entry->parent?->title }} · {{ $entry->author?->name }} · {{ $entry->created_at->format('M j, Y') }}</p>@endif
      @if($entry->kind !== 'pet')<p>{{ \Illuminate\Support\Str::limit($entry->body, 100) }}</p>@endif
    </div>
  </a>
  @if(($kind ?? '') === 'saved')
    <div class="card-copy">@include('partials.action', ['target' => $entry, 'action' => 'unsave', 'label' => 'Remove from saved'])</div>
  @endif
</article>