<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(name: 'indonesia_regions', key: 'code', keyType: 'integer', incrementing: false, timestamps: false)]
class IndonesiaRegion extends Model
{
}
