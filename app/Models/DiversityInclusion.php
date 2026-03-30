<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiversityInclusion extends Model
{
    protected $table = 'diversity_inclusion';
    protected $primaryKey = 'diversity_id';

    protected $fillable = [
        'home_page',
        'diversity_category',
        'description',
        'pdf_file',
    ];
}