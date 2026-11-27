<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessCode extends Model
{
    protected $primaryKey = 'code_date';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['code_date', 'code', 'created_by'];
}
