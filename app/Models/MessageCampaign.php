<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageCampaign extends Model
{
    protected $fillable = ['name', 'body', 'template_id', 'audience', 'status', 'recipients_count', 'scheduled_at', 'created_by'];

    protected function casts(): array { return ['scheduled_at' => 'datetime']; }

    public function template() { return $this->belongsTo(MessageTemplate::class); }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
