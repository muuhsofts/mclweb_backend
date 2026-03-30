<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValuesHome extends Model
{
    use HasFactory;

    protected $table = 'values_homes';
    protected $primaryKey = 'values_home_id';
    
    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}