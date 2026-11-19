<section class="panel member-section" aria-labelledby="members-heading">
  <h2 id="members-heading">Meet the community</h2>
  <div class="member-grid">
    @foreach(config('community.members') as $name => $index)
      <div class="member">
        <img src="{{ asset('members/member-'.$index.'.jpg') }}" alt="" loading="lazy">
        <strong>{{ $name }}</strong>
      </div>
    @endforeach
  </div>
</section>