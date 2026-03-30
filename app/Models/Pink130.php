<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pink130 extends Model
{
    use HasFactory;

    protected $table = 'pink_130s';
    protected $primaryKey = 'pink_id';

    protected $fillable = [
        'category',
        'description',
        'video',
        'pdf_file',
    ];
}