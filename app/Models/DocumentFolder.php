<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentFolder extends Model
{
    protected $guarded = [];

    public function parent(){return $this->belongsTo(self::class,'parent_id');}
    public function children(){return $this->hasMany(self::class,'parent_id');}
    public function documents(){return $this->hasMany(Document::class,'folder_id');}
    public function department(){return $this->belongsTo(Department::class);}
    public function branch(){return $this->belongsTo(Branch::class);}
    public function owner(){return $this->belongsTo(User::class,'owner_id');}
    public function permissions(){return $this->morphMany(DocumentPermission::class,'permissible');}

    /** Root → leaf chain including self. */
    public function ancestors(): \Illuminate\Support\Collection
    {
        $chain = collect([$this]);
        $node = $this->parent;
        while ($node) { $chain->prepend($node); $node = $node->parent; }
        return $chain;
    }
}
