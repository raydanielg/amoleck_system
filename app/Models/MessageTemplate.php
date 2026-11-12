<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['key', 'name', 'category', 'body', 'variables', 'is_active'];

    protected function casts(): array { return ['variables' => 'array', 'is_active' => 'boolean']; }

    /** Replace {{var}} placeholders. Throws on unresolved. */
    public function render(array $vars): string
    {
        $body = preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($vars) {
            if (! array_key_exists($m[1], $vars)) {
                throw new \RuntimeException("Unresolved template variable: {{".$m[1]."}}");
            }

            return (string) $vars[$m[1]];
        }, $this->body);

        return $body;
    }

    public function placeholders(): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $this->body, $m);

        return array_unique($m[1]);
    }
}
