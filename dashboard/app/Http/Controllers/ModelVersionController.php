<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\ModelVersion;
use Illuminate\Http\Request;

class ModelVersionController extends Controller
{
    public function index()
    {
        $models = ModelVersion::paginate(10);

        return view('model-versions.index', compact('models'));
    }

    public function create()
    {
        return view('model-versions.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100', 'version' => 'required|string|max:50', 'checksum_sha256' => 'required|string|size:64|unique:model_versions', 'license' => 'required|string|max:50']);
        $model = ModelVersion::create($request->only(['name', 'version', 'weight_filename', 'checksum_sha256', 'license', 'source_url', 'image_size']) + ['class_list' => json_encode(['person', 'cell phone']), 'weight_filename' => $request->name, 'created_at' => now()]);
        AuditHelper::log('model_created', 'model_version', (string) $model->id);

        return redirect()->route('model-versions.index')->with('success', 'Model version created');
    }

    public function show(ModelVersion $modelVersion)
    {
        return view('model-versions.show', compact('modelVersion'));
    }

    public function edit(ModelVersion $modelVersion)
    {
        return view('model-versions.edit', compact('modelVersion'));
    }

    public function update(Request $request, ModelVersion $modelVersion)
    {
        $request->validate(['name' => 'required|string|max:100', 'version' => 'required|string|max:50', 'license' => 'required|string|max:50']);
        $modelVersion->update($request->only(['name', 'version', 'license']));
        AuditHelper::log('model_updated', 'model_version', (string) $modelVersion->id);

        return redirect()->route('model-versions.index')->with('success', 'Updated');
    }

    public function destroy(ModelVersion $modelVersion)
    {
        if ($modelVersion->analysisJobs()->exists()) {
            return back()->withErrors(['name' => 'Cannot delete model with jobs']);
        }
        $modelVersion->delete();
        AuditHelper::log('model_deleted', 'model_version', (string) $modelVersion->id);

        return redirect()->route('model-versions.index')->with('success', 'Deleted');
    }
}
