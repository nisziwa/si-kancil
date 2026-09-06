<?php

namespace App\Http\Controllers;

use App\Models\MasterAkun;
use App\Models\MasterKegiatan;
use App\Models\MasterKomponen;
use App\Models\MasterOutput;
use App\Models\MasterProgram;
use App\Models\MasterRincianPok;
use App\Models\MasterSubOutput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterPokController extends Controller
{
    /**
     * Mapping internal tabel Master POK. Whitelist: tab dari URL hanya boleh
     * bernilai salah satu key berikut; selain itu abort(404).
     */
    private const TABS = [
        'program' => [
            'label' => 'Program',
            'model' => MasterProgram::class,
            'table' => 'master_program',
            'kode_field' => 'kode_program',
            'nama_field' => 'nama_program',
            'columns' => [
                ['key' => 'kode_program', 'label' => 'Kode Program'],
                ['key' => 'nama_program', 'label' => 'Nama Program'],
            ],
            'with' => [],
            'searchable' => ['kode_program', 'nama_program'],
            'order_by' => ['kode_program', 'asc'],
            'filters' => [],
        ],
        'kegiatan' => [
            'label' => 'Kegiatan',
            'model' => MasterKegiatan::class,
            'table' => 'master_kegiatan',
            'kode_field' => 'kode_kegiatan',
            'nama_field' => 'nama_kegiatan',
            'columns' => [
                ['key' => 'kode_kegiatan', 'label' => 'Kode Kegiatan'],
                ['key' => 'nama_kegiatan', 'label' => 'Nama Kegiatan'],
                ['key' => 'program', 'label' => 'Program', 'parent' => true, 'pk' => 'kode_program', 'pn' => 'nama_program'],
            ],
            'with' => ['program'],
            'searchable' => ['kode_kegiatan', 'nama_kegiatan'],
            'order_by' => ['kode_kegiatan', 'asc'],
            'filters' => [
                ['field' => 'program_id', 'table' => 'master_program', 'relation' => 'program', 'label' => 'Program', 'code' => 'kode_program', 'name' => 'nama_program'],
            ],
        ],
        'output' => [
            'label' => 'Output',
            'model' => MasterOutput::class,
            'table' => 'master_output',
            'kode_field' => 'kode_output',
            'nama_field' => 'nama_output',
            'columns' => [
                ['key' => 'kode_output', 'label' => 'Kode Output'],
                ['key' => 'nama_output', 'label' => 'Nama Output'],
                ['key' => 'kegiatan', 'label' => 'Kegiatan', 'parent' => true, 'pk' => 'kode_kegiatan', 'pn' => 'nama_kegiatan'],
            ],
            'with' => ['kegiatan.program'],
            'searchable' => ['kode_output', 'nama_output'],
            'order_by' => ['kode_output', 'asc'],
            'filters' => [
                ['field' => 'kegiatan_id', 'table' => 'master_kegiatan', 'relation' => 'kegiatan', 'label' => 'Kegiatan', 'code' => 'kode_kegiatan', 'name' => 'nama_kegiatan'],
            ],
        ],
        'sub-output' => [
            'label' => 'Sub Output',
            'model' => MasterSubOutput::class,
            'table' => 'master_sub_output',
            'kode_field' => 'kode_sub_output',
            'nama_field' => 'nama_sub_output',
            'columns' => [
                ['key' => 'kode_sub_output', 'label' => 'Kode Sub Output'],
                ['key' => 'nama_sub_output', 'label' => 'Nama'],
                ['key' => 'output', 'label' => 'Output', 'parent' => true, 'pk' => 'kode_output', 'pn' => 'nama_output'],
            ],
            'with' => ['output.kegiatan.program'],
            'searchable' => ['kode_sub_output', 'nama_sub_output'],
            'order_by' => ['kode_sub_output', 'asc'],
            'filters' => [
                ['field' => 'output_id', 'table' => 'master_output', 'relation' => 'output', 'label' => 'Output', 'code' => 'kode_output', 'name' => 'nama_output'],
            ],
        ],
        'komponen' => [
            'label' => 'Komponen',
            'model' => MasterKomponen::class,
            'table' => 'master_komponen',
            'kode_field' => 'kode_komponen',
            'nama_field' => 'nama_komponen',
            'columns' => [
                ['key' => 'kode_komponen', 'label' => 'Kode Komponen'],
                ['key' => 'nama_komponen', 'label' => 'Nama Komponen'],
                ['key' => 'subOutput', 'label' => 'Sub Output', 'parent' => true, 'pk' => 'kode_sub_output', 'pn' => 'nama_sub_output'],
            ],
            'with' => ['subOutput.output.kegiatan.program'],
            'searchable' => ['kode_komponen', 'nama_komponen'],
            'order_by' => ['kode_komponen', 'asc'],
            'filters' => [
                ['field' => 'sub_output_id', 'table' => 'master_sub_output', 'relation' => 'subOutput', 'label' => 'Sub Output', 'code' => 'kode_sub_output', 'name' => 'nama_sub_output'],
            ],
        ],
        'akun' => [
            'label' => 'Akun',
            'model' => MasterAkun::class,
            'table' => 'master_akun',
            'kode_field' => 'kode_akun',
            'nama_field' => 'nama_akun',
            'columns' => [
                ['key' => 'kode_akun', 'label' => 'Kode Akun'],
                ['key' => 'nama_akun', 'label' => 'Nama Akun'],
            ],
            'with' => [],
            'searchable' => ['kode_akun', 'nama_akun'],
            'order_by' => ['kode_akun', 'asc'],
            'filters' => [],
        ],
        'rincian' => [
            'label' => 'Rincian POK',
            'model' => MasterRincianPok::class,
            'table' => 'master_rincian_pok',
            'kode_field' => 'rincian',
            'nama_field' => 'rincian',
            'columns' => [
                ['key' => 'program', 'label' => 'Program', 'parent' => true, 'pk' => 'kode_program', 'pn' => 'nama_program'],
                ['key' => 'kegiatan', 'label' => 'Kegiatan', 'parent' => true, 'pk' => 'kode_kegiatan', 'pn' => 'nama_kegiatan'],
                ['key' => 'output', 'label' => 'Output', 'parent' => true, 'pk' => 'kode_output', 'pn' => 'nama_output'],
                ['key' => 'subOutput', 'label' => 'Sub Output', 'parent' => true, 'pk' => 'kode_sub_output', 'pn' => 'nama_sub_output'],
                ['key' => 'komponen', 'label' => 'Komponen', 'parent' => true, 'pk' => 'kode_komponen', 'pn' => 'nama_komponen'],
                ['key' => 'akun', 'label' => 'Akun', 'parent' => true, 'pk' => 'kode_akun', 'pn' => 'nama_akun'],
                ['key' => 'rincian', 'label' => 'Rincian'],
            ],
            'with' => ['program', 'kegiatan', 'output', 'subOutput', 'komponen', 'akun'],
            'searchable' => ['rincian'],
            'order_by' => ['rincian', 'asc'],
            'filters' => [
                ['field' => 'program_id', 'table' => 'master_program', 'relation' => 'program', 'label' => 'Program', 'code' => 'kode_program', 'name' => 'nama_program'],
                ['field' => 'kegiatan_id', 'table' => 'master_kegiatan', 'relation' => 'kegiatan', 'label' => 'Kegiatan', 'code' => 'kode_kegiatan', 'name' => 'nama_kegiatan'],
            ],
        ],
    ];

    public function index(Request $request)
    {
        $tab = $request->input('tab', 'rincian');
        $config = $this->resolveTab($tab);
        $model = $config['model'];

        $query = $model::with($config['with']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            if ($search !== '') {
                $query->where(function ($q) use ($search, $config) {
                    foreach ($config['searchable'] as $i => $field) {
                        $op = $i === 0 ? 'where' : 'orWhere';
                        $q->{$op}($field, 'like', '%'.$search.'%');
                    }
                });
            }
        }

        foreach ($config['filters'] as $filter) {
            if ($request->filled($filter['field'])) {
                $query->where($filter['field'], $request->input($filter['field']));
            }
        }

        if (! (bool) $request->input('show_inactive', false)) {
            $query->where('is_active', true);
        }

        $rows = $query->orderBy($config['order_by'][0], $config['order_by'][1])
            ->paginate(15)
            ->withQueryString();

        $filterOptions = $this->filterOptions($config['filters']);

        return view('master_pok.index', compact('tab', 'config', 'rows', 'filterOptions'));
    }

    public function create(Request $request, string $tab)
    {
        $config = $this->resolveTab($tab);

        return view('master_pok.create', [
            'tab' => $tab,
            'config' => $config,
            'parents' => $this->parentsOptions($config, null),
        ]);
    }

    public function store(Request $request, string $tab)
    {
        $config = $this->resolveTab($tab);

        $validated = $this->validateData($request, $config);

        $config['model']::create($validated + ['is_active' => true]);

        return redirect()->route('master-pok.index', ['tab' => $tab])->with('success', $config['label'].' berhasil ditambahkan.');
    }

    public function edit(Request $request, string $tab, int $id)
    {
        $config = $this->resolveTab($tab);
        $row = $config['model']::with($config['with'])->findOrFail($id);

        return view('master_pok.edit', [
            'tab' => $tab,
            'config' => $config,
            'row' => $row,
            'parents' => $this->parentsOptions($config, $row),
        ]);
    }

    public function update(Request $request, string $tab, int $id)
    {
        $config = $this->resolveTab($tab);
        $row = $config['model']::findOrFail($id);

        $validated = $this->validateData($request, $config, $row);

        $row->update($validated);

        return redirect()->route('master-pok.index', ['tab' => $tab])->with('success', $config['label'].' berhasil diperbarui.');
    }

    /**
     * Aktifkan / nonaktifkan data. Tidak ada hard delete.
     */
    public function toggle(Request $request, string $tab, int $id)
    {
        $config = $this->resolveTab($tab);
        $row = $config['model']::findOrFail($id);

        $row->update(['is_active' => ! $row->is_active]);

        $status = $row->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return redirect()->route('master-pok.index', $this->backParams($request, $tab))->with('success', $config['label'].' berhasil '.$status.'.');
    }

    protected function resolveTab(string $tab): array
    {
        abort_if(! isset(self::TABS[$tab]), 404);

        return self::TABS[$tab];
    }

    protected function backParams(Request $request, string $tab): array
    {
        $params = ['tab' => $tab];

        foreach (['search', 'show_inactive'] as $key) {
            if ($request->filled($key)) {
                $params[$key] = $request->input($key);
            }
        }

        return $params;
    }

    protected function filterOptions(array $filters): array
    {
        $options = [];
        foreach ($filters as $filter) {
            $model = $this->modelForTable($filter['table']);
            $options[$filter['field']] = $model::query()
                ->where('is_active', true)
                ->orderBy($filter['code'])
                ->get()
                ->map(fn ($m) => (object) [
                    'value' => $m->id,
                    'label' => ($m->{$filter['code']} ?? '').' - '.($m->{$filter['name']} ?? ''),
                ]);
        }

        return $options;
    }

    /**
     * Opsi parent untuk dropdown form. Hanya parent aktif yang dipakai input
     * baru; untuk edit, parent milik data itu tetap disertakan walau nonaktif
     * agar form tidak kehilangan nilai terpilih.
     */
    protected function parentsOptions(array $config, ?object $row): array
    {
        $parents = [];
        $relations = $this->parentRelations($config);

        foreach ($relations as $relation => $table) {
            $active = $this->modelForTable($table)::query()
                ->where('is_active', true)
                ->orderBy($this->codeFieldForTable($table))
                ->get();

            if ($row && $row->{$relation}) {
                $active = $active->push($row->{$relation})->unique('id')->values();
            }

            $parents[$relation] = $active;
        }

        return $parents;
    }

    protected function parentRelations(array $config): array
    {
        if ($config['table'] === 'master_program' || $config['table'] === 'master_akun') {
            return [];
        }

        if ($config['table'] === 'master_kegiatan') {
            return ['program' => 'master_program'];
        }

        if ($config['table'] === 'master_output') {
            return ['kegiatan' => 'master_kegiatan'];
        }

        if ($config['table'] === 'master_sub_output') {
            return ['output' => 'master_output'];
        }

        if ($config['table'] === 'master_komponen') {
            return ['subOutput' => 'master_sub_output'];
        }

        if ($config['table'] === 'master_rincian_pok') {
            return [
                'program' => 'master_program',
                'kegiatan' => 'master_kegiatan',
                'output' => 'master_output',
                'subOutput' => 'master_sub_output',
                'komponen' => 'master_komponen',
                'akun' => 'master_akun',
            ];
        }

        return [];
    }

    protected function modelForTable(string $table): string
    {
        return match ($table) {
            'master_program' => MasterProgram::class,
            'master_kegiatan' => MasterKegiatan::class,
            'master_output' => MasterOutput::class,
            'master_sub_output' => MasterSubOutput::class,
            'master_komponen' => MasterKomponen::class,
            'master_akun' => MasterAkun::class,
            'master_rincian_pok' => MasterRincianPok::class,
            default => abort(404),
        };
    }

    protected function codeFieldForTable(string $table): string
    {
        return match ($table) {
            'master_program' => 'kode_program',
            'master_kegiatan' => 'kode_kegiatan',
            'master_output' => 'kode_output',
            'master_sub_output' => 'kode_sub_output',
            'master_komponen' => 'kode_komponen',
            'master_akun' => 'kode_akun',
            'master_rincian_pok' => 'rincian',
            default => abort(404),
        };
    }

    protected function validateData(Request $request, array $config, ?object $row = null): array
    {
        $table = $config['table'];
        $ignore = $row ? $row->id : null;

        $messages = [
            'required' => 'Kolom ini wajib diisi.',
            'string' => 'Kolom ini harus berupa teks.',
            'exists' => 'Data induk yang dipilih tidak valid atau tidak aktif.',
        ];

        $rules = [];

        if ($table === 'master_rincian_pok') {
            $rules = [
                'program_id' => ['required', 'integer', Rule::exists('master_program', 'id')->where('is_active', true)],
                'kegiatan_id' => ['required', 'integer', Rule::exists('master_kegiatan', 'id')->where('is_active', true)],
                'output_id' => ['required', 'integer', Rule::exists('master_output', 'id')->where('is_active', true)],
                'sub_output_id' => ['required', 'integer', Rule::exists('master_sub_output', 'id')->where('is_active', true)],
                'komponen_id' => ['required', 'integer', Rule::exists('master_komponen', 'id')->where('is_active', true)],
                'akun_id' => ['required', 'integer', Rule::exists('master_akun', 'id')->where('is_active', true)],
                'rincian' => [
                    'required',
                    'string',
                    'max:2000',
                    Rule::unique('master_rincian_pok', 'rincian')
                        ->where(fn ($q) => $q
                            ->where('program_id', $request->input('program_id'))
                            ->where('kegiatan_id', $request->input('kegiatan_id'))
                            ->where('output_id', $request->input('output_id'))
                            ->where('sub_output_id', $request->input('sub_output_id'))
                            ->where('komponen_id', $request->input('komponen_id'))
                            ->where('akun_id', $request->input('akun_id')))
                        ->ignore($ignore),
                ],
            ];

            return $request->validate($rules, $messages);
        }

        $codeField = $config['kode_field'];
        $nameField = $config['nama_field'];

        $rules[$codeField] = ['required', 'string', 'max:255', Rule::unique($table, $codeField)->ignore($ignore)];
        $rules[$nameField] = ['required', 'string', 'max:255'];

        if ($config['filters']) {
            $filter = $config['filters'][0];
            $rules[$filter['field']] = ['required', 'integer', Rule::exists($filter['table'], 'id')->where('is_active', true)];
        }

        return $request->validate($rules, $messages);
    }
}