<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OurStandard extends Model
{
    protected $table = 'our_standards';
    protected $primaryKey = 'our_id';

    protected $fillable = [
        'standard_category',
        'standard_file',
        'weblink',
        'description',
    ];
}
