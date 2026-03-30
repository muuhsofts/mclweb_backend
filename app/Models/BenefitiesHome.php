<?php

// app/Models/BenefitiesHome.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenefitiesHome extends Model
{
    use HasFactory;

    // ADD THIS! This is the most critical fix for the 500 error.
    protected $fillable = [
        'heading',
        'description',
        'home_img',
    ];

    // Also define primary key if it's not 'id'
    protected $primaryKey = 'benefit_home_id';
}