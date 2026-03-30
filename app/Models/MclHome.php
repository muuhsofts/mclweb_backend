<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MclHome extends Model
{
    use HasFactory;

    protected $table = 'mcl_home';
    protected $primaryKey = 'mcl_home_id';
    protected $fillable = ['heading', 'mcl_home_img', 'description'];
}