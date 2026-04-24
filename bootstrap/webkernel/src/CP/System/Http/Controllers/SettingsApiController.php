<?php declare(strict_types=1);

namespace Webkernel\CP\System\Http\Controllers;

use Webkernel\CP\System\Models\WebkernelSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = WebkernelSetting::query();

        if ($registry = $request->query('registry')) {
            $query->where('registry', $registry);
        }

        if ($vendor = $request->query('vendor')) {
            $query->where('vendor', $vendor);
        }

        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($request->boolean('custom_only')) {
            $query->where('is_custom', true);
        }

        return response()->json($query->get(['id', 'category', 'key', 'value', 'type', 'registry', 'vendor', 'module']));
    }

    public function show(string $dotKey): JsonResponse
    {
        try {
            [$category, $key] = explode('.', $dotKey, 2);
        } catch (\Throwable) {
            return response()->json(['error' => 'Invalid key format. Use category.key'], 400);
        }

        $setting = WebkernelSetting::forCategory($category)->where('key', $key)->first();

        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        return response()->json([
            'id'       => $setting->id,
            'category' => $setting->category,
            'key'      => $setting->key,
            'value'    => $setting->resolvedValue(),
            'type'     => $setting->type,
            'label'    => $setting->label,
            'registry' => $setting->registry,
            'vendor'   => $setting->vendor,
            'module'   => $setting->module,
        ]);
    }

    public function update(Request $request, string $dotKey): JsonResponse
    {
        try {
            [$category, $key] = explode('.', $dotKey, 2);
        } catch (\Throwable) {
            return response()->json(['error' => 'Invalid key format. Use category.key'], 400);
        }

        $modifier = $request->user()?->email ?? 'api';

        WebkernelSetting::set("{$category}.{$key}", $request->input('value'), $modifier);

        return response()->json(['message' => 'Setting updated'], 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'key' => 'required|string',
            'value' => 'nullable',
            'type' => 'required|in:text,password,boolean,integer,select,textarea,json',
            'label' => 'required|string',
            'description' => 'nullable|string',
            'registry' => 'nullable|string',
            'vendor' => 'nullable|string',
            'module' => 'nullable|string',
        ]);

        $validated['is_custom'] = true;
        $validated['introduced_in_version'] = \WEBKERNEL_VERSION;

        $setting = WebkernelSetting::create($validated);

        return response()->json($setting, 201);
    }

    public function delete(string $dotKey): JsonResponse
    {
        try {
            [$category, $key] = explode('.', $dotKey, 2);
        } catch (\Throwable) {
            return response()->json(['error' => 'Invalid key format. Use category.key'], 400);
        }

        $setting = WebkernelSetting::forCategory($category)->where('key', $key)->first();

        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        if (!$setting->is_custom) {
            return response()->json(['error' => 'Cannot delete system settings'], 403);
        }

        $setting->delete();

        return response()->json(['message' => 'Setting deleted'], 200);
    }
}
