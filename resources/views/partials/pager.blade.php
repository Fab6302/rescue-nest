@if($items->hasPages())
  <nav class="toolbar pagination" aria-label="Pagination">
    @if($items->previousPageUrl()) <a class="button secondary" href="{{ $items->previousPageUrl() }}">Previous</a> @endif
    <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
    @if($items->nextPageUrl()) <a class="button secondary" href="{{ $items->nextPageUrl() }}">Next</a> @endif
  </nav>
@endif