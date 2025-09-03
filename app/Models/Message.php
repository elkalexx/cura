<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'body',
        'direction',
        'outlook_message_id',
        'outlook_conversation_id',
        'attachments',
        'x_g5_id',
        'received_at'
    ];

    protected $appends = [
        'body_summary',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function senderParticipant(): HasOne
    {
        return $this->hasOne(MessageParticipant::class)
            ->where('kind', 'from')
            ->with('contact');
    }

    public function envelope(): HasMany
    {
        return $this->hasMany(MessageParticipant::class);
    }

    protected function bodySummary(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $text = strip_tags($this->body);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/\s+/', ' ', trim($text));

                return Str::limit($text);
            }
        );
    }

    protected function casts(): array
    {
        return [
            'x_g5_id' => 'string',
        ];
    }
}
