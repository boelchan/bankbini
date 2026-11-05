<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Table(name: 'posts', key: 'id')]
#[Fillable(['judul', 'slug', 'isi', 'gambar', 'publish'])]
class Post extends Model
{
}
