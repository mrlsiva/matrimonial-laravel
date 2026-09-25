<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Caste;
use App\Models\City;
use App\Models\EducationLevel;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\State;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * One CRUD screen for every lookup table. Each type maps to a model and, for
 * child tables (castes, cities), a parent model used for grouping and filtering.
 */
class MasterDataController extends Controller
{
    private const TYPES = [
        'religions' => ['model' => Religion::class, 'label' => 'Religions'],
        'castes' => ['model' => Caste::class, 'label' => 'Castes', 'parent' => Religion::class, 'parent_key' => 'religion_id', 'parent_label' => 'Religion'],
        'education-levels' => ['model' => EducationLevel::class, 'label' => 'Education Levels'],
        'occupations' => ['model' => Occupation::class, 'label' => 'Occupations'],
        'states' => ['model' => State::class, 'label' => 'States'],
        'cities' => ['model' => City::class, 'label' => 'Cities', 'parent' => State::class, 'parent_key' => 'state_id', 'parent_label' => 'State'],
    ];

    public function index(Request $request, string $type): View
    {
        $config = $this->config($type);
        $model = $config['model'];
        $parentKey = $config['parent_key'] ?? null;

        $items = $model::query()
            ->when($parentKey, fn ($q) => $q->with(Str::beforeLast($parentKey, '_id')))
            ->when($parentKey && $request->parent, fn ($q) => $q->where($parentKey, $request->parent))
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.masters.index', [
            'type' => $type,
            'config' => $config,
            'items' => $items,
            'parents' => isset($config['parent']) ? $config['parent']::orderBy('name')->pluck('name', 'id') : collect(),
            'types' => collect(self::TYPES)->map(fn ($c) => $c['label']),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $config = $this->config($type);
        $config['model']::create($this->validated($request, $config));

        return back()->with('success', Str::singular($config['label']).' added.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $config = $this->config($type);
        $item = $config['model']::findOrFail($id);
        $item->update($this->validated($request, $config, $item));

        return back()->with('success', Str::singular($config['label']).' updated.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $config = $this->config($type);

        try {
            $config['model']::findOrFail($id)->delete();
        } catch (QueryException) {
            return back()->with('error', 'This record is in use and cannot be deleted. Deactivate it instead.');
        }

        return back()->with('success', Str::singular($config['label']).' deleted.');
    }

    private function config(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    private function validated(Request $request, array $config, ?Model $item = null): array
    {
        $table = (new $config['model'])->getTable();
        $parentKey = $config['parent_key'] ?? null;

        $unique = Rule::unique($table, 'name')->ignore($item?->id);
        if ($parentKey) {
            $unique->where($parentKey, $request->input($parentKey));
        }

        $rules = [
            'name' => ['required', 'string', 'max:100', $unique],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
        if ($parentKey) {
            $rules[$parentKey] = ['required', Rule::exists((new $config['parent'])->getTable(), 'id')];
        }

        $data = $request->validate($rules);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
