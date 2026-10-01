<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class NotificationRecipient extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $table = 'notification_recipients';
 
    protected $fillable = [
        'notification_id',
        'user_id',
        'read_at',
        'archived_at',
    ];
 
    protected $casts = [
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
    ];
 
    /**
     * Get the notification
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
 
    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    /**
     * Scope: Get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at')
            ->whereNull('archived_at');
    }
 
    /**
     * Scope: Get archived notifications
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
 
    /**
     * Scope: Get read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }
 
    /**
     * Mark as read
     */
    public function markAsRead()
    {
        return $this->update(['read_at' => now()]);
    }
 
    /**
     * Mark as unread
     */
    public function markAsUnread()
    {
        return $this->update(['read_at' => null]);
    }
 
    /**
     * Archive notification
     */
    public function archive()
    {
        return $this->update(['archived_at' => now()]);
    }
 
    /**
     * Restore from archive
     */
    public function restore()
    {
        return $this->update(['archived_at' => null]);
    }
 
    /**
     * Check if unread
     */
    public function isUnread()
    {
        return $this->read_at === null && $this->archived_at === null;
    }
 
    /**
     * Check if read
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }
 
    /**
     * Check if archived
     */
    public function isArchived()
    {
        return $this->archived_at !== null;
    }
}