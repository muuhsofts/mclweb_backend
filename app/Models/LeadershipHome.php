<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadershipHome extends Model
{
    use HasFactory;

    protected $primaryKey = 'leadership_home_id';

    protected $table = 'leadership_homes';

    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];
}