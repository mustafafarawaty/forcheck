<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key-value application settings controlled by administration.
 */
class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];
}
