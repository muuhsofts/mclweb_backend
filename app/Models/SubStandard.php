<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubStandard extends Model
{
    protected $table = 'sub_standard';
    protected $primaryKey = 'subStandard_id';

    protected $fillable = [
        'subStandard_category',
        'our_id',
        'description',
    ];

    /**
     * Get the parent standard that owns the sub-standard.
     */
    public function ourStandard()
    {
        return $this->belongsTo(OurStandard::class, 'our_id', 'our_id');
    }
}
