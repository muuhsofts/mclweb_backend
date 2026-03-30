<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcategoryWeDo extends Model
{
    use HasFactory;

    protected $table = 'subcategory_we_do';
    protected $primaryKey = 'subcategory_id';

    protected $fillable = [
        'what_we_do_id',
        'subcategory',
        'description',
        'img_url',
        'web_url',
    ];

    public function whatWeDo()
    {
        return $this->belongsTo(WhatWeDo::class, 'what_we_do_id');
    }
}