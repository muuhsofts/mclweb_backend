<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatWeDo extends Model
{
    use HasFactory;

    protected $table = 'tbl_what_we_do';
    protected $primaryKey = 'what_we_do_id';

    protected $fillable = [
        'category',
        'description',
        'img_file',
    ];
}