<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    protected $fillable = ['event_key', 'label', 'template_id', 'recipient_source', 'enabled', 'delay_minutes'];

    protected function casts(): array { return ['enabled' => 'boolean']; }

    public function template() { return $this->belongsTo(MessageTemplate::class); }
}
