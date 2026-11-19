@extends('layout')
@php
  $definition = config('community.kinds')[$kind];
  $heading = ($entry ? 'Edit ' : 'Add ').strtolower($definition[1]);
  $action = $entry ? '/edit/'.$entry->id : '/create/'.$kind.($parent ? '/'.$parent->id : '');
  $options = ['area' => config('community.areas'), 'animal' => config('community.animals'),
    'category' => config('community.categories'), 'availability' => ['available', 'unavailable'],
    'rating' => [1, 2, 3, 4, 5], 'urgency' => ['routine', 'urgent'], 'reason' => ['Spam', 'Harassment', 'Misleading', 'Inappropriate', 'Other']];
  $labels = ['animal'=>'Animal type','age'=>'Age in years','health'=>'Health notes','care'=>$kind === 'application' ? 'Care plan' : 'Care needs',
    'experience'=>'Pet-care experience','contact'=>'Public contact details','hours'=>'Opening hours','price'=>'Price (BDT)','rating'=>'Rating out of 5',
    'date'=>$kind === 'lost' ? 'Last-seen date' : ($kind === 'found' ? 'Found date' : 'Sighting date')];
  $labels += ['weight'=>'Weight (kg)', 'diet'=>'Food habits', 'favorites'=>'Favorite foods', 'father'=>'Father', 'mother'=>'Mother'];
  $optional = ['weight', 'diet', 'favorites', 'father', 'mother'];
  $bodyLabel = ['update'=>'Update','comment'=>'Comment or reply','application'=>'Short introduction','question'=>'Question or reply','report'=>'Report details'][$kind] ?? 'Description';
@endphp
@section('title', $heading)
@section('content')
  <section class="panel narrow">
    <h1>{{ $heading }}</h1>
    @if($parent)<p>For {{ $parent->title }}</p>@endif
    <form method="post" action="{{ $action }}" enctype="multipart/form-data">
      @csrf
      @unless(in_array($kind, config('community.fixed_titles'), true))
        <label>{{ in_array($kind, ['pet','shop','product']) ? 'Name' : 'Title' }}<input name="title" required maxlength="150" value="{{ old('title', $entry?->title) }}"></label>
      @endunless
      <label>{{ $bodyLabel }}<textarea name="body" @required($kind !== 'product') maxlength="4000" rows="5">{{ old('body', $entry?->body) }}</textarea></label>
      @foreach($definition[2] as $field)
        @php($value = old($field, in_array($field, ['area','animal']) ? $entry?->$field : ($entry?->details[$field] ?? null)))
        <label>{{ $labels[$field] ?? ucfirst($field) }}
          @if(isset($options[$field]))
            <select name="{{ $field }}" required><option value="">Choose</option>
              @foreach($options[$field] as $option)<option value="{{ $option }}" @selected((string)$value === (string)$option)>{{ ucfirst((string)$option) }}</option>@endforeach
            </select>
          @elseif(in_array($field, ['health','care','experience']))
            <textarea name="{{ $field }}" required maxlength="1000" rows="3">{{ $value }}</textarea>
          @else
            <input name="{{ $field }}" value="{{ $value }}" @required(!in_array($field, $optional)) maxlength="250" type="{{ in_array($field,['date','time']) ? $field : (in_array($field,['age','price','weight']) ? 'number' : 'text') }}" @if(in_array($field,['age','price','weight'])) min="0" step="0.01" @endif>
          @endif
        </label>
      @endforeach
      @if($definition[3])
        <label>Photo<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" @required(!$entry?->photo && $kind !== 'update')></label>
        <p class="muted">JPG, PNG, or WebP, up to 4 MB.</p>
      @endif
      <button class="button">{{ $entry ? 'Save changes' : 'Publish' }}</button>
    </form>
  </section>
@endsection