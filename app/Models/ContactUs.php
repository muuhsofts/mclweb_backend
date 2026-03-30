<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    use HasFactory;

    protected $primaryKey = 'contactus_id';

    protected $fillable = [
        'category',
        'description',
        'img_file',
        'url_link',
    ];
}