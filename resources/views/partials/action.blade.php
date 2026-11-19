<form class="inline" method="post" action="/entry/{{ $target->id }}/action">
  @csrf
  <input type="hidden" name="action" value="{{ $action }}">
  <button class="button secondary">{{ $label }}</button>
</form>