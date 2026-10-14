<?php

namespace App\Http\Controllers\Api\Lookup;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared CRUD for the small "lookup" tables (regions, degree levels, fields of
 * study, tawjihi branches). index() is public (dropdown data
 * for a frontend); store/update/destroy are wired up only behind admin routes.
 * CityController overrides a couple of methods to also handle region_id.
 *
 * @property class-string<Model> $model
 */
abstract class LookupController extends Controller
{
    protected string $model;

    public function index()
    {
        return $this->model::orderBy('name')->get(['id', 'name']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique((new $this->model)->getTable(), 'name')],
        ]);

        $record = $this->model::create($data);

        return response()->json($record, 201);
    }

    public function update(Request $request, int $id)
    {
        $record = $this->model::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique($record->getTable(), 'name')->ignore($record->id)],
        ]);

        $record->update($data);

        return $record;
    }

    public function destroy(int $id)
    {
        $this->model::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
