<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GivingBackHome extends Model
{
    use HasFactory;

    protected $table = 'giving_back_homes';

    protected $primaryKey = 'giving_back_id';

    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}