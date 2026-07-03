<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class WhatsappMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'recipient_phone',
        'message_type',
        'template_name',
        'content',
        'media_url',
        'status',
        'wa_message_id',
        'failed_reason',
        'scheduled_at',
        'sent_at',
        'created_by'
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime'
        ];
    }

    
}
