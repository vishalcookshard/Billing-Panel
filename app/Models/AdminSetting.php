<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    use HasFactory;

    // Mass-assignment protection: never allow sensitive fields to be assigned
    protected $guarded = ['id', 'key', 'encrypted'];

    public static function get($key, $default = null)
    {
        $row = static::where('key', $key)->first();

        if (!$row) {
            return $default;
        }

        $value = $row->value;

        if ($row->encrypted) {
            try {
                return decrypt($value);
            } catch (\Throwable $e) {
                return $default;
            }
        }

        return $value;
    }

    public static function set($key, $value, $encrypted = false)
    {
        if ($encrypted) {
            $value = encrypt($value);
        }

        return static::updateOrCreate(['key' => $key], ['value' => $value, 'encrypted' => $encrypted]);
    }
}
