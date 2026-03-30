<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sustainability extends Model
{
    protected $table = 'sustainability';
    protected $primaryKey = 'sustain_id';

    protected $fillable = [
        'sustain_category',
        'description',
        'weblink',
        'sustain_pdf_file',
    ];
}