<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MclGroup extends Model
{
    use HasFactory;

    protected $table = 'mcl_groups';
    protected $primaryKey = 'mcl_id';
    protected $fillable = ['mcl_category', 'image_file', 'description', 'weblink', 'home_page'];
}