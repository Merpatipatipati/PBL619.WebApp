<?php

namespace App\Http\Controllers;

use App\Models\Misi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MisiController extends Controller
{


    // Tampilkan semua misi
    public function index()
    {
        $missions = Misi::all();
        return view('misi.index', compact('missions'));
    }

    // Form tambah misi
    public function create()
    {
        return view('misi.create');
    }

    // Simpan misi baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_misi'      => 'required|string|max:255',
            'deskripsi_misi' => 'required|string',
            'status_misi'    => 'required|in:aktif,tidak aktif',
            'tipe_misi'      => 'required|in:harian,mingguan',
            'poin'           => 'required|integer|min:0',
        ]);

        $validated['id_admin'] = Auth::id();           // admin yang sedang login
        Misi::create($validated);

        return redirect()
            ->route('misi.index')
            ->with('success', 'Misi berhasil ditambahkan!');
    }

    // Form edit misi
    public function edit($id_misi)
    {
        $mission = Misi::findOrFail($id_misi);
        return view('misi.edit', compact('mission'));
    }

    // Update misi
    public function update(Request $request, $id_misi)
    {
        $validated = $request->validate([
            'nama_misi'      => 'required|string|max:255',
            'deskripsi_misi' => 'required|string',
            'status_misi'    => 'required|in:aktif,tidak aktif',
            'tipe_misi'      => 'required|in:harian,mingguan',
            'poin'           => 'required|integer|min:0',
        ]);

        $mission = Misi::findOrFail($id_misi);
        $mission->update($validated);

        return redirect()
            ->route('misi.index')
            ->with('success', 'Misi berhasil diperbarui!');
    }

    // Hapus misi (dipanggil via Ajax)
    public function destroy($id_misi)
    {
        Misi::findOrFail($id_misi)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Misi berhasil dihapus!',
        ]);
    }


    // GET /api/user/misi  → global misi + auto misi
    public function getAllMisi()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $globalMisi = Misi::where('status_misi', 'aktif')->get();
        $autoMisi = \App\Models\UserMisi::where('user_id', $user->id)
                        ->where('is_auto_generated', true)
                        ->get(); // we fetch all so frontend can see completed ones too

        $merged = collect();

        foreach ($globalMisi as $item) {
            $merged->push([
                'id_misi'       => $item->id_misi,
                'nama_misi'     => $item->nama_misi,
                'deskripsi_misi'=> $item->deskripsi_misi,
                'poin'          => $item->poin,
                'status_misi'   => $item->status_misi,
                'tipe_misi'     => $item->tipe_misi,
                'is_auto_generated' => false,
                'admin'         => $item->admin->nama_admin ?? null,
            ]);
        }

        foreach ($autoMisi as $item) {
            $merged->push([
                'id_misi'       => $item->id, // map user_misi id to id_misi for frontend
                'nama_misi'     => $item->nama_misi,
                'deskripsi_misi'=> $item->deskripsi_misi,
                'poin'          => $item->poin,
                'status_misi'   => $item->status,
                'tipe_misi'     => 'harian', // auto missions are daily
                'is_auto_generated' => true,
                'parameter_type'=> $item->parameter_type,
                'target_value'  => $item->target_value,
                'auto_completed'=> $item->status == 'selesai',
                'completed_at'  => $item->completed_at ? $item->completed_at->toIso8601String() : null,
                'expires_at'    => $item->expires_at ? $item->expires_at->toIso8601String() : null,
                'expiry_type'   => 'daily',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar misi berhasil diambil',
            'data'    => $merged,
        ], 200);
    }

    // GET /api/user/misi/{id}
    public function getMisiDetail($id)
    {
        $misi = Misi::with('admin')->find($id);

        if (!$misi) {
            return response()->json([
                'success' => false,
                'message' => 'Misi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail misi berhasil diambil',
            'data'    => [
                'id_misi'       => $misi->id_misi,
                'nama_misi'     => $misi->nama_misi,
                'deskripsi_misi'=> $misi->deskripsi_misi,
                'poin'          => $misi->poin,
                'status_misi'   => $misi->status_misi,
                'tipe_misi'     => $misi->tipe_misi,
                'admin'         => $misi->admin->nama_admin ?? null,
                'tanggal_dibuat'=> $misi->created_at->format('d-m-Y H:i'),
            ],
        ], 200);
    }

    // POST /api/user/misi/auto
    public function createAutoMission(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'nama_misi'      => 'required|string',
            'deskripsi_misi' => 'required|string',
            'poin'           => 'required|integer',
            'parameter_type' => 'required|string',
            'target_value'   => 'nullable|numeric',
            'trigger_condition' => 'nullable|string',
            'trigger_min_value' => 'nullable|numeric',
            'trigger_max_value' => 'nullable|numeric',
        ]);

        // Cek apakah ada misi auto yg masih aktif untuk parameter ini
        $existing = \App\Models\UserMisi::where('user_id', $user->id)
            ->where('is_auto_generated', true)
            ->where('parameter_type', $validated['parameter_type'])
            ->where('status', 'aktif')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Masih ada misi aktif untuk parameter ini',
                'data' => null
            ], 409);
        }

        $misi = \App\Models\UserMisi::create([
            'user_id' => $user->id,
            'nama_misi' => $validated['nama_misi'],
            'deskripsi_misi' => $validated['deskripsi_misi'],
            'poin' => $validated['poin'],
            'parameter_type' => $validated['parameter_type'],
            'target_value' => $validated['target_value'] ?? null,
            'trigger_condition' => $validated['trigger_condition'] ?? null,
            'trigger_min_value' => $validated['trigger_min_value'] ?? null,
            'trigger_max_value' => $validated['trigger_max_value'] ?? null,
            'is_auto_generated' => true,
            'status' => 'aktif',
            'expires_at' => now()->addHours(6), // Misal kadaluarsa dalam 6 jam
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Auto mission created',
            'data' => $misi
        ], 201);
    }

    // GET /api/user/misi/active
    public function getActiveMissionByParameter(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $parameter = $request->query('parameter');

        $active = \App\Models\UserMisi::where('user_id', $user->id)
            ->where('is_auto_generated', true)
            ->where('parameter_type', $parameter)
            ->where('status', 'aktif')
            ->first();

        if ($active) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $active->id, // auto_mission_service expects 'id'
                    'nama_misi' => $active->nama_misi,
                ]
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak ada misi aktif',
            'data' => null
        ], 200);
    }

    // PATCH /api/user/misi/{id}/complete
    public function completeMission($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $misi = \App\Models\UserMisi::where('user_id', $user->id)->find($id);

        if (!$misi) {
            return response()->json(['success' => false, 'message' => 'Mission not found'], 404);
        }

        $misi->update([
            'status' => 'selesai',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mission completed',
            'data' => $misi
        ], 200);
    }

    // DELETE /api/user/misi/auto/cleanup
    public function cleanupExpiredMissions()
    {
        $user = Auth::user();
        if (!$user) return response()->json(['success' => false], 401);

        $expired = \App\Models\UserMisi::where('user_id', $user->id)
            ->where('is_auto_generated', true)
            ->where('status', 'aktif')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        return response()->json([
            'success' => true,
            'message' => "Cleaned up $expired expired missions"
        ], 200);
    }
}