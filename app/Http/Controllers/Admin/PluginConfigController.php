<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Models\PluginConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PluginConfigController extends Controller
{
    public function index(Plugin $plugin)
    {
        $this->authorize('manage-plugins');

        return PluginConfig::where('plugin_id', $plugin->id)->get();
    }

    public function update(Request $request, Plugin $plugin)
    {
        $this->authorize('manage-plugins');

        $validated = $request->validate([
            'key' => 'required|string|max:100',
            'value' => 'required|string|max:2000',
            'encrypted' => 'boolean',
        ]);

        $key = $validated['key'];
        $value = $validated['value'];
        $encrypted = (bool)($validated['encrypted'] ?? false);

        if ($encrypted) {
            $value = encrypt($value);
        }

        $config = PluginConfig::updateOrCreate(['plugin_id' => $plugin->id, 'key' => $key], ['value' => $value, 'encrypted' => $encrypted]);

        Log::info('Plugin config updated', ['plugin' => $plugin->key, 'key' => $key]);

        return $config;
    }
}
