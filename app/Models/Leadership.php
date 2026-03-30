<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leadership extends Model
{
    use HasFactory;

    protected $table = 'leadership';

    protected $primaryKey = 'leadership_id';

    protected $fillable = [
        'position',
        'level', // Add level
        'leader_name',
        'leader_image',
        'description',
    ];

    public $timestamps = true;
}