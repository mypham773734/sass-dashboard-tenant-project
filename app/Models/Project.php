<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant; 

class Project extends Model
{
    public function tenant(){
        return $this->belongsTo(Tenant::class); 
    }
}
