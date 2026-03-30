<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInfo extends Model
{
    use HasFactory;

    protected $table = 'contact_info';
    protected $primaryKey = 'contact_info_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contactus_id',
        // 'department_category' has been removed.
        'phone_one',
        'phone_two',
        'email_address',
        'webmail_address',
        'location',
    ];

    /**
     * Get the parent ContactUs model.
     */
    public function contactUs(): BelongsTo
    {
        return $this->belongsTo(ContactUs::class, 'contactus_id');
    }
}