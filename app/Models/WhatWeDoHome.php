<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatWeDoHome extends Model
{
    use HasFactory;

    protected $table = 'what_we_do_homes';
    protected $primaryKey = 'what_we_do_id';

    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}