<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    use HasFactory;

    protected $primaryKey = 'benefit_id';

    protected $fillable = [
        'category',
        'img_file',
        'description',
    ];
}