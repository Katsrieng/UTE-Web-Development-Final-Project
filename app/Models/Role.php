<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Role extends Model
{
    protected $fillable = ['name','slug','description','is_protected'];
    protected $casts = ['is_protected'=>'boolean'];
    public function permissions(): BelongsToMany { return $this->belongsToMany(Permission::class); }
    public function users(): HasMany { return $this->hasMany(User::class, 'role', 'slug'); }
}
