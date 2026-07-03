<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class WhatsappSetting extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'instance_token',
        'instance_name',
        'phone_number',
        'status',
        'connected_since',
        'last_ping',
        'messages_sent_today',
        'failed_count',
        'gateway_url',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'connected_since' => 'datetime',
            'last_ping' => 'datetime',
            'is_active' => 'boolean'
        ];
    }

    
}
