<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactHome extends Model
{
    use HasFactory;

    protected $table = 'contact_home'; // Explicitly set the table name
    protected $primaryKey = 'cont_home_id';

    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}