<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $fillable = ['kind', 'user_id', 'parent_id', 'title', 'body', 'area', 'animal', 'photo', 'status', 'details'];
    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function photoUrl(): ?string
    {
        if (!$this->photo) {
            return null;
        }
        return str_starts_with($this->photo, "uploads/")
            ? url("/entry/".$this->id."/photo")
            : asset($this->photo);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}