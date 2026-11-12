<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = [];

    use \Illuminate\Database\Eloquent\SoftDeletes;
    public function folder(){return $this->belongsTo(DocumentFolder::class,'folder_id');}
    public function versions(){return $this->hasMany(DocumentVersion::class)->orderByDesc('version');}
    public function current(){return $this->hasOne(DocumentVersion::class)->ofMany('version','max');}
    public function owner(){return $this->belongsTo(User::class,'owner_id');}
    public function department(){return $this->belongsTo(Department::class);}
    public function branch(){return $this->belongsTo(Branch::class);}
    public function permissions(){return $this->morphMany(DocumentPermission::class,'permissible');}
    public function shares(){return $this->morphMany(DocumentShare::class,'shareable');}
    public function starredBy(){return $this->hasMany(DocumentStar::class);}
}
