<?php
namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommunityController
{
    private function definition(string $kind): array
    {
        return config('community.kinds')[$kind] ?? abort(404);
    }

    public function home()
    {
        $pets = Entry::where('kind', 'pet')->where('status', 'open')->oldest('id')->take(10)->get();
        return view('home', compact('pets'));
    }

    public function browse(Request $request, string $kind)
    {
        abort_unless(in_array($kind, [...config('community.public'), 'notices'], true), 404);
        $kinds = $kind === 'notices' ? ['lost', 'found'] : [$kind];
        $filters = $request->validate([
            'q' => 'nullable|string|max:120', 'breed' => 'nullable|string|max:100',
            'area' => ['nullable', Rule::in(config('community.areas'))],
            'animal' => ['nullable', Rule::in(config('community.animals'))],
            'category' => ['nullable', Rule::in(config('community.categories'))], 'availability' => 'nullable|in:available,unavailable',
            'notice' => 'nullable|in:lost,found', 'status' => 'nullable|in:all,adopted,withdrawn,reunited,closed,in_progress,rescued',
            'assigned' => 'nullable|in:1', 'urgency' => 'nullable|in:routine,urgent', 'age_group' => 'nullable|in:young,adult', 'date' => 'nullable|date_format:Y-m-d',
            'min' => 'nullable|numeric|min:0', 'max' => 'nullable|numeric|min:0',
        ]);
        $query = Entry::query()->with(['parent', 'author'])
            ->whereIn('kind', $kinds)->whereNotIn('status', ['removed', 'deleted']);
        if ($kind === 'product') {
            $query->whereHas('parent', fn ($parent) => $parent->where('status', 'open'));
        }
        $onlyAssigned = $kind === 'rescue' && ($filters['assigned'] ?? null) === '1';
        if ($onlyAssigned) {
            abort_unless($request->user()?->role === 'rescuer', 403);
            $query->where('details->rescuer_id', $request->user()->id);
        }
        $selectedStatus = $filters['status'] ?? ($onlyAssigned ? 'all' : 'open');
        if (in_array($kind, ['adoption', 'rescue', 'lost', 'found', 'notices'], true) && $selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }
        if (!empty($filters['notice']) && $kind === 'notices') {
            $query->where('kind', $filters['notice']);
        }
        if (!empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(fn ($match) => $match->where('title', 'like', $term)->orWhere('body', 'like', $term));
        }
        foreach (['area', 'animal', 'breed', 'category', 'availability', 'date', 'urgency'] as $filter) {
            if (empty($filters[$filter])) {
                continue;
            }
            $column = in_array($filter, ['area', 'animal'], true) ? $filter : 'details->'.$filter;
            if ($kind === 'product' && $filter === 'area') {
                $query->whereHas('parent', fn ($shop) => $shop->where(fn ($area) => $area->where('area', $filters[$filter])->orWhere('details->secondary_area', $filters[$filter])));
            } else {
                if ($kind === 'shop' && $filter === 'area') {
                    $query->where(fn ($area) => $area->where('area', $filters[$filter])->orWhere('details->secondary_area', $filters[$filter]));
                } else {
                    $query->where($column, $filters[$filter]);
                }
            }
        }
        if ($kind === 'adoption' && !empty($filters['age_group'])) {
            $query->whereRaw('CAST('.$this->jsonValue('age').' AS DECIMAL(12,2)) '.($filters['age_group'] === 'young' ? '<' : '>=').' 1');
        }
        if ($kind === 'product') {
            foreach (['min' => '>=', 'max' => '<='] as $key => $operator) {
                if (isset($filters[$key])) {
                    $query->whereRaw('CAST('.$this->jsonValue('price').' AS DECIMAL(12,2)) '.$operator.' ?', [(float) $filters[$key]]);
                }
            }
        }
        return view('browse', [
            'kind' => $kind,
            'heading' => $kind === 'notices' ? 'Lost & found' : $this->definition($kind)[0],
            'entries' => $query->latest('id')->paginate(12)->withQueryString(),
        ]);
    }

    private function jsonValue(string $field): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "json_extract(details, '$.$field')"
            : "json_unquote(json_extract(details, '$.$field'))";
    }

    private function canView(Entry $entry, ?int $userId, ?string $role): bool
    {
        if (in_array($entry->status, ['deleted', 'removed'], true)) {
            return false;
        }
        if ($entry->kind === 'report') {
            return $role === 'moderator' || ($userId && $entry->user_id === $userId);
        }
        if (in_array($entry->kind, ['application', 'sighting'], true)) {
            return $userId && ($entry->user_id === $userId || $entry->parent?->user_id === $userId);
        }
        if ($entry->parent && in_array($entry->kind, ['product', 'comment', 'review', 'question', 'update'], true)) {
            return $this->canView($entry->parent, $userId, $role);
        }
        return in_array($entry->kind, config('community.public'), true);
    }

    public function show(Request $request, Entry $entry)
    {
        $user = $request->user();
        abort_unless($this->canView($entry, $user?->id, $user?->role), 404);
        $entry->load(['author', 'parent.author']);
        // Filter private child rows before pagination, so totals expose no private requests.
        $query = $entry->children()->with('author')->whereNotIn('status', ['removed', 'deleted'])->latest('id');
        if ($entry->kind === 'adoption' || $entry->kind === 'lost') {
            if (!$user || $user->id !== $entry->user_id) {
                $query->where('user_id', $user?->id ?? 0);
            }
        }
        $query->where('kind', '!=', 'report');
        $children = $query->paginate(16);
        $rating = $entry->kind === 'shop' ? $entry->children()->where('kind', 'review')->where('status', 'open')
            ->get()->avg(fn ($review) => $review->details['rating']) : null;
        $isSaved = $user && DB::table('saved_items')->where(['user_id' => $user->id, 'entry_id' => $entry->id])->exists();
        return view('entry', compact('entry', 'children', 'rating', 'isSaved'));
    }

    private function authorizeCreate(Request $request, string $kind, ?Entry $parent): void
    {
        $definition = $this->definition($kind);
        $user = $request->user();
        $allowedParents = $kind === 'report' ? config('community.reportable') : explode('|', $definition[4] ?? '');
        abort_unless(in_array($parent?->kind ?? '', $allowedParents, true), 404);
        if ($parent) {
            abort_unless($this->canView($parent, $user->id, $user->role), 404);
        }
        if ($kind === 'shop') {
            abort_unless(in_array($user->role, ['shop', 'moderator'], true), 403);
        }
        if (in_array($kind, ['product', 'update'], true)) {
            abort_unless($parent->user_id === $user->id && $parent->status === 'open', 403);
        }
        if (in_array($kind, ['application', 'sighting'], true)) {
            abort_unless($parent->status === 'open' && $parent->user_id !== $user->id, 403);
        }
        if ($kind === 'question') {
            abort_unless(in_array($parent->status, ['open', 'question', 'reviewed', 'accepted'], true), 403);
        }
    }

    public function editor(Request $request, string $kind, ?Entry $parent = null)
    {
        $this->authorizeCreate($request, $kind, $parent);
        return view('editor', ['kind' => $kind, 'parent' => $parent, 'entry' => null]);
    }

    public function canEdit(Entry $entry, $user): bool
    {
        return $user && $entry->status === 'open'
            && !in_array($entry->kind, ['application', 'sighting', 'question', 'report'], true)
            && ($entry->user_id === $user->id || ($entry->kind === 'shop' && $user->role === 'moderator'));
    }

    public function edit(Request $request, Entry $entry)
    {
        abort_unless($this->canEdit($entry, $request->user()), 403);
        return view('editor', ['kind' => $entry->kind, 'parent' => $entry->parent, 'entry' => $entry]);
    }

    private function input(Request $request, string $kind, ?Entry $entry = null): array
    {
        $metadata = $this->definition($kind);
        $fixed = in_array($kind, config('community.fixed_titles'), true);
        $rules = [
            'body' => $kind === 'product' ? 'nullable|string|max:4000' : 'required|string|max:4000',
        ];
        if (!$fixed) {
            $rules['title'] = 'required|string|max:150';
        }
        if ($metadata[3]) {
            $rules['photo'] = [($entry?->photo || $kind === 'update') ? 'nullable' : 'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
        }
        $fieldRules = [
            'weight' => 'nullable|numeric|min:0.01|max:500',
            'diet' => 'nullable|string|max:250', 'favorites' => 'nullable|string|max:250',
            'father' => 'nullable|string|max:250', 'mother' => 'nullable|string|max:250',
            'area' => ['required', Rule::in(config('community.areas'))],
            'animal' => ['required', Rule::in(config('community.animals'))],
            'breed' => 'required|string|max:100', 'age' => 'required|numeric|min:0|max:100',
            'health' => 'required|string|max:1000', 'care' => 'required|string|max:1000', 'contact' => 'required|string|max:200',
            'experience' => 'required|string|max:1000', 'address' => 'required|string|max:250', 'hours' => 'required|string|max:150',
            'category' => ['required', Rule::in(config('community.categories'))],
            'price' => 'required|numeric|min:0|max:99999999', 'availability' => 'required|in:available,unavailable',
            'rating' => 'required|integer|between:1,5', 'date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'urgency' => 'required|in:routine,urgent', 'place' => 'required|string|max:200', 'time' => 'required|date_format:H:i',
            'reason' => 'required|in:Spam,Harassment,Misleading,Inappropriate,Other',
        ];
        foreach ($metadata[2] as $field) {
            $rules[$field] = $fieldRules[$field];
        }
        $data = $request->validate($rules);
        $details = collect($data)->except(['title', 'body', 'area', 'animal', 'photo'])->all();
        return [
            'title' => $fixed ? $metadata[1] : $data['title'],
            'body' => $data['body'] ?? '',
            'area' => $data['area'] ?? null,
            'animal' => $data['animal'] ?? null,
            'details' => $details,
        ];
    }

    public function media(Request $request, Entry $entry)
    {
        $user = $request->user();
        abort_unless($this->canView($entry, $user?->id, $user?->role), 404);
        abort_unless(str_starts_with($entry->photo ?? "", "uploads/"), 404);
        $file = storage_path("app/community-photos/".basename($entry->photo));
        abort_unless(is_file($file), 404);
        return response()->file($file, ["Cache-Control" => "private, no-store", "X-Content-Type-Options" => "nosniff"]);
    }

    private function photo(Request $request, Entry $entry): void
    {
        if (!$request->hasFile('photo')) {
            return;
        }
        $upload = $request->file('photo');
        $filename = $upload->hashName();
        $upload->move(storage_path('app/community-photos'), $filename);
        $entry->photo = 'uploads/'.$filename;
    }

    public function create(Request $request, string $kind, ?Entry $parent = null)
    {
        $this->authorizeCreate($request, $kind, $parent);
        $input = $this->input($request, $kind);
        $entry = DB::transaction(function () use ($request, $kind, $parent, $input) {
            if ($parent) {
                $parent = Entry::query()->lockForUpdate()->findOrFail($parent->id);
                $this->authorizeCreate($request, $kind, $parent);
            }
            $uid = $request->user()->id;
            if (in_array($kind, ['application', 'review'], true)) {
                $existing = $parent->children()->where('kind', $kind)->where('user_id', $uid);
                $statuses = $kind === 'review' ? ['open'] : ['open', 'question', 'accepted'];
                if ($existing->whereIn('status', $statuses)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['body' => 'You already have an active '.$this->definition($kind)[1].' here.']);
                }
            }
            $entry = new Entry($input + ['kind' => $kind, 'user_id' => $uid, 'parent_id' => $parent?->id, 'status' => 'open']);
            $this->photo($request, $entry);
            $entry->save();
            if ($kind === 'question') {
                $parent->update(['status' => $parent->status === 'accepted' ? 'accepted' : ($parent->user_id === $uid ? 'open' : 'question')]);
            }
            return $entry;
        });
        return redirect('/entry/'.($kind === 'report' ? $entry->id : ($parent?->id ?? $entry->id)))->with('status', 'Published.');
    }

    public function update(Request $request, Entry $entry)
    {
        abort_unless($this->canEdit($entry, $request->user()), 403);
        $data = $this->input($request, $entry->kind, $entry);
        DB::transaction(function () use ($entry, $request, $data) {
            $entry = Entry::query()->lockForUpdate()->findOrFail($entry->id);
            abort_unless($this->canEdit($entry, $request->user()), 403);
            $entry->fill($data);
            $this->photo($request, $entry);
            $entry->save();
        });
        return redirect('/entry/'.$entry->id)->with('status', 'Changes saved.');
    }

    public function action(Request $request, Entry $entry)
    {
        $user = $request->user();
        $action = $request->validate([
            'action' => 'required|in:save,unsave,delete,withdraw,accept,decline,complete,reunite,close,review,dismiss,remove,claim,resolve',
        ])['action'];
        DB::transaction(function () use ($entry, $user, $action) {
            if ($entry->parent_id) {
                Entry::query()->lockForUpdate()->findOrFail($entry->parent_id);
            }
            $entry = Entry::query()->lockForUpdate()->findOrFail($entry->id);
            abort_unless($this->canView($entry, $user->id, $user->role), 404);
            $owner = $entry->user_id === $user->id;
            $parentOwner = $entry->parent?->user_id === $user->id;
            $active = in_array($entry->status, ['open', 'question', 'accepted'], true);
            if (in_array($action, ['save', 'unsave'], true)) {
                abort_unless(in_array($entry->kind, config('community.saveable'), true), 403);
                $item = ['user_id' => $user->id, 'entry_id' => $entry->id];
                $action === 'save' ? DB::table('saved_items')->insertOrIgnore($item) : DB::table('saved_items')->where($item)->delete();
                return;
            }
            $newStatus = match ($action) {
                'delete' => $owner && $entry->status === 'open' && in_array($entry->kind, ['update', 'comment', 'review', 'product'], true) ? 'deleted' : null,
                'withdraw' => $owner && $active && in_array($entry->kind, ['adoption', 'application'], true) ? 'withdrawn' : null,
                'accept', 'decline' => $parentOwner && $active && $entry->kind === 'application' && $entry->parent->status === 'open' ? ($action === 'accept' ? 'accepted' : 'declined') : null,
                'complete' => $parentOwner && $entry->status === 'accepted' && $entry->kind === 'application' && $entry->parent->status === 'open' ? 'completed' : null,
                'reunite' => $owner && $entry->kind === 'lost' && $entry->status === 'open' ? 'reunited' : null,
                'close' => $owner && in_array($entry->kind, ['found', 'rescue'], true) && $entry->status === 'open' ? 'closed' : null,
                'claim' => $user->role === 'rescuer' && !$owner && $entry->kind === 'rescue' && $entry->status === 'open' ? 'in_progress' : null,
                'resolve' => $user->role === 'rescuer' && $entry->kind === 'rescue' && $entry->status === 'in_progress'
                    && ($entry->details['rescuer_id'] ?? null) === $user->id ? 'rescued' : null,
                'review' => $parentOwner && $entry->kind === 'sighting' && in_array($entry->status, ['open', 'question'], true) ? 'reviewed' : null,
                'dismiss', 'remove' => $user->role === 'moderator' && $entry->kind === 'report' && $entry->status === 'open' ? ($action === 'dismiss' ? 'dismissed' : 'resolved') : null,
            };
            abort_unless($newStatus, 403);
            $details = $entry->details ?? [];
            if ($action === 'claim') {
                $details['rescuer_id'] = $user->id;
                $details['rescuer'] = $user->name;
            }
            if ($action === 'accept') {
                abort_if($entry->parent->children()->where('kind', 'application')->where('status', 'accepted')->whereKeyNot($entry->id)->exists(), 409, 'Decline the previously accepted request first.');
            }
            if ($action === 'complete' || ($action === 'withdraw' && $entry->kind === 'adoption')) {
                $listing = $action === 'complete' ? $entry->parent : $entry;
                if ($action === 'complete') {
                    $listing->update(['status' => 'adopted']);
                }
                $others = $listing->children()->where('kind', 'application')->whereIn('status', ['open', 'question', 'accepted']);
                if ($action === 'complete') {
                    $others->whereKeyNot($entry->id);
                }
                foreach ($others->get() as $other) {
                    $other->update(['status' => 'closed', 'details' => ($other->details ?? []) + [
                        'decision' => $action === 'complete' ? 'The pet was handed over to another applicant.' : 'The listing owner withdrew this adoption.',
                    ]]);
                }
            }
            if (in_array($action, ['dismiss', 'remove'], true)) {
                $details['decision'] = $action === 'dismiss' ? 'Report dismissed.' : 'Reported content removed.';
                $details['moderator'] = $user->name;
                if ($action === 'remove') {
                    $entry->parent?->update(['status' => 'removed']);
                }
            }
            $entry->update(['status' => $newStatus, 'details' => $details]);
        });
        if ($action === 'unsave' && str_ends_with(url()->previous(), '/saved')) {
            return redirect('/saved')->with('status', 'Removed from saved items.');
        }
        return redirect($action === 'delete' ? ($entry->parent_id ? '/entry/'.$entry->parent_id : '/') : '/entry/'.$entry->id)
            ->with('status', 'Updated.');
    }

    public function saved(Request $request)
    {
        $ids = DB::table('saved_items')->where('user_id', $request->user()->id)->select('entry_id');
        return view('browse', ['kind' => 'saved', 'heading' => 'Saved items', 'entries' => Entry::whereIn('id', $ids)
            ->whereNotIn('status', ['removed', 'deleted'])->with(['parent', 'author'])->latest('id')->paginate(12)]);
    }

    public function myShops(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['shop', 'moderator'], true), 403);
        $shops = Entry::where('kind', 'shop')->where('status', 'open')
            ->where('user_id', $request->user()->id)->latest('id')->paginate(12);

        return view('my-shops', compact('shops'));
    }

    public function activity(Request $request)
    {
        $entries = Entry::where('user_id', $request->user()->id)
            ->whereNotIn('status', ['deleted', 'removed'])->with(['parent', 'author'])->latest('id')->paginate(16);
        return view('browse', ['kind' => 'activity', 'heading' => 'My activity', 'entries' => $entries]);
    }

    public function moderation(Request $request)
    {
        abort_unless($request->user()->role === 'moderator', 403);
        return view('browse', ['kind' => 'moderation', 'heading' => 'Moderation reports', 'entries' => Entry::where('kind', 'report')
            ->with(['parent', 'author'])->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")->latest('id')->paginate(12)]);
    }
}