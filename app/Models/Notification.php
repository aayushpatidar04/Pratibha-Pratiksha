<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\AsCollection;
 
class Notification extends Model
{
    use HasFactory;
 
    protected $table = 'notifications';
 
    protected $fillable = [
        'title',
        'body',
        'type',
        'payload',
        'actionable_type',
        'actionable_id',
        'action_url',
        'action_label',
        'created_by',
        'status',
    ];
 
    /**
     * Cast JSON payload to array
     */
    protected $casts = [
        'payload' => AsCollection::class,
    ];
 
    /**
     * Get the user who created this notification
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    /**
     * Get all recipients of this notification
     */
    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }
 
    /**
     * Get the polymorphic model (Invoice, Room, etc.)
     */
    public function actionable()
    {
        return $this->morphTo();
    }
 
    /**
     * Mark as sent status
     */
    public function markAsSent()
    {
        $this->update(['status' => 'sent']);
    }
 
    /**
     * Mark as failed status
     */
    public function markAsFailed($reason = null)
    {
        $this->update(['status' => 'failed']);
        if ($reason) {
            $this->logs()->create([
                'event' => 'failed',
                'message' => $reason,
            ]);
        }
    }
 
    /**
     * Add recipient to notification
     */
    public function addRecipient($userId)
    {
        return $this->recipients()->firstOrCreate(
            ['user_id' => $userId],
            ['created_at' => now()]
        );
    }
 
    /**
     * Add multiple recipients
     */
    public function addRecipients(array $userIds)
    {
        foreach ($userIds as $userId) {
            $this->addRecipient($userId);
        }
    }
 
    /**
     * Get the actionable model polymorphically
     * Usage: $notification->getActionable() returns the Invoice/Room/etc.
     */
    public function getActionable()
    {
        if ($this->actionable_type && $this->actionable_id) {
            return $this->actionable;
        }
        return null;
    }
}