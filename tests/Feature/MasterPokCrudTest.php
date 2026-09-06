<?php

namespace Tests\Feature;

use App\Models\MasterAkun;
use App\Models\MasterKegiatan;
use App\Models\MasterKomponen;
use App\Models\MasterOutput;
use App\Models\MasterProgram;
use App\Models\MasterSubOutput;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterPokCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function chain(array $program, array $kegiatan): array
    {
        $program = MasterProgram::create(['kode_program' => $program['kode_program'], 'nama_program' => $program['nama_program'], 'is_active' => true]);
        $kegiatan = MasterKegiatan::create(['program_id' => $program->id, 'kode_kegiatan' => $kegiatan['kode_kegiatan'], 'nama_kegiatan' => $kegiatan['nama_kegiatan'], 'is_active' => true]);
        $output = MasterOutput::create(['kegiatan_id' => $kegiatan->id, 'kode_output' => $kegiatan['kode_kegiatan'].'.BMA', 'nama_output' => 'Output '.$kegiatan['nama_kegiatan'], 'is_active' => true]);
        $sub = MasterSubOutput::create(['output_id' => $output->id, 'kode_sub_output' => '007', 'nama_sub_output' => 'Sub Output '.$kegiatan['nama_kegiatan'], 'is_active' => true]);
        $komponen = MasterKomponen::create(['sub_output_id' => $sub->id, 'kode_komponen' => '005', 'nama_komponen' => 'Komponen', 'is_active' => true]);
        $akun = MasterAkun::create(['kode_akun' => '521213', 'nama_akun' => 'Belanja Honor', 'is_active' => true]);

        return compact('program', 'kegiatan', 'output', 'sub', 'komponen', 'akun');
    }

    public function test_invalid_tab_returns_404(): void
    {
        $this->actingAs($this->user)
            ->get(route('master-pok.index', ['tab' => 'random']))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get('/master-pok/random/create')
            ->assertNotFound();
    }

    public function test_new_record_defaults_to_active(): void
    {
        $this->actingAs($this->user)
            ->post(route('master-pok.store', ['tab' => 'program']), [
                'kode_program' => '054.01.GG',
                'nama_program' => 'Program Informasi Statistik',
            ])
            ->assertRedirect(route('master-pok.index', ['tab' => 'program']));

        $this->assertDatabaseHas('master_program', [
            'kode_program' => '054.01.GG',
            'is_active' => true,
        ]);
    }

    public function test_deactivate_then_reactivate_succeeds(): void
    {
        $program = MasterProgram::create(['kode_program' => 'AAA', 'nama_program' => 'Program A', 'is_active' => true]);

        $this->actingAs($this->user)
            ->patch(route('master-pok.toggle', ['tab' => 'program', 'id' => $program->id]))
            ->assertRedirect(route('master-pok.index', ['tab' => 'program']));

        $this->assertDatabaseHas('master_program', ['id' => $program->id, 'is_active' => false]);

        $this->actingAs($this->user)
            ->patch(route('master-pok.toggle', ['tab' => 'program', 'id' => $program->id]))
            ->assertRedirect(route('master-pok.index', ['tab' => 'program']));

        $this->assertDatabaseHas('master_program', ['id' => $program->id, 'is_active' => true]);
    }

    public function test_index_only_shows_active_by_default(): void
    {
        MasterProgram::create(['kode_program' => 'LIVE', 'nama_program' => 'PROGRAM AKTIF', 'is_active' => true]);
        MasterProgram::create(['kode_program' => 'OFF', 'nama_program' => 'PROGRAM NONAKTIF', 'is_active' => false]);

        $this->actingAs($this->user)
            ->get(route('master-pok.index', ['tab' => 'program']))
            ->assertOk()
            ->assertSee('PROGRAM AKTIF')
            ->assertDontSee('PROGRAM NONAKTIF');
    }

    public function test_show_inactive_checkbox_renders_inactive_data(): void
    {
        MasterProgram::create(['kode_program' => 'LIVE', 'nama_program' => 'PROGRAM AKTIF', 'is_active' => true]);
        MasterProgram::create(['kode_program' => 'OFF', 'nama_program' => 'PROGRAM NONAKTIF', 'is_active' => false]);

        $this->actingAs($this->user)
            ->get(route('master-pok.index', ['tab' => 'program', 'show_inactive' => 1]))
            ->assertOk()
            ->assertSee('PROGRAM AKTIF')
            ->assertSee('PROGRAM NONAKTIF');
    }

    public function test_child_cannot_be_created_without_parent(): void
    {
        $this->actingAs($this->user)
            ->from(route('master-pok.create', ['tab' => 'output']))
            ->post(route('master-pok.store', ['tab' => 'output']), [
                'kode_output' => '8130.BMA',
                'nama_output' => 'Output',
            ])
            ->assertSessionHasErrors('kegiatan_id');
    }

    public function test_duplicate_rincian_same_combination_rejected(): void
    {
        $c = $this->chain(
            ['kode_program' => '054.01.GG', 'nama_program' => 'Program A'],
            ['kode_kegiatan' => '8130', 'nama_kegiatan' => 'Kegiatan A'],
        );

        $payload = [
            'program_id' => $c['program']->id,
            'kegiatan_id' => $c['kegiatan']->id,
            'output_id' => $c['output']->id,
            'sub_output_id' => $c['sub']->id,
            'komponen_id' => $c['komponen']->id,
            'akun_id' => $c['akun']->id,
            'rincian' => 'Honor petugas pendataan lapangan survei SKP',
        ];

        $this->actingAs($this->user)
            ->post(route('master-pok.store', ['tab' => 'rincian']), $payload)
            ->assertRedirect(route('master-pok.index', ['tab' => 'rincian']));

        $this->actingAs($this->user)
            ->from(route('master-pok.create', ['tab' => 'rincian']))
            ->post(route('master-pok.store', ['tab' => 'rincian']), $payload)
            ->assertSessionHasErrors('rincian');

        $this->assertDatabaseCount('master_rincian_pok', 1);
    }

    public function test_same_rincian_on_different_branch_allowed(): void
    {
        $c1 = $this->chain(
            ['kode_program' => '054.01.GG', 'nama_program' => 'Program A'],
            ['kode_kegiatan' => '8130', 'nama_kegiatan' => 'Kegiatan A'],
        );
        $c2 = $this->chain(
            ['kode_program' => '054.01.GG', 'nama_program' => 'Program A'],
            ['kode_kegiatan' => '2904', 'nama_kegiatan' => 'Kegiatan B'],
        );

        $build = fn (array $c) => [
            'program_id' => $c['program']->id,
            'kegiatan_id' => $c['kegiatan']->id,
            'output_id' => $c['output']->id,
            'sub_output_id' => $c['sub']->id,
            'komponen_id' => $c['komponen']->id,
            'akun_id' => $c['akun']->id,
            'rincian' => 'Honor petugas pendataan lapangan survei SKP',
        ];

        $this->actingAs($this->user)
            ->post(route('master-pok.store', ['tab' => 'rincian']), $build($c1))
            ->assertRedirect(route('master-pok.index', ['tab' => 'rincian']));

        $this->actingAs($this->user)
            ->post(route('master-pok.store', ['tab' => 'rincian']), $build($c2))
            ->assertRedirect(route('master-pok.index', ['tab' => 'rincian']));

        $this->assertDatabaseCount('master_rincian_pok', 2);
    }

    public function test_edit_updates_existing_record(): void
    {
        $kegiatan = $this->chain(
            ['kode_program' => '054.01.GG', 'nama_program' => 'Program A'],
            ['kode_kegiatan' => '8130', 'nama_kegiatan' => 'Kegiatan Lama'],
        )['kegiatan'];

        $this->actingAs($this->user)
            ->put(route('master-pok.update', ['tab' => 'kegiatan', 'id' => $kegiatan->id]), [
                'program_id' => $kegiatan->program_id,
                'kode_kegiatan' => '8130',
                'nama_kegiatan' => 'Kegiatan Baru',
            ])
            ->assertRedirect(route('master-pok.index', ['tab' => 'kegiatan']));

        $this->assertDatabaseHas('master_kegiatan', ['id' => $kegiatan->id, 'nama_kegiatan' => 'Kegiatan Baru']);
    }
}