<?php

namespace App\Http\Controllers;

use App\Models\Misi;
use App\Models\UserMisiProgress;
use App\Models\User;
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


    // GET /api/user/misi  → hanya misi aktif
    public function getAllMisi()
    {
        $misi = Misi::where('status_misi', 'aktif')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar misi aktif berhasil diambil',
            'data'    => $misi->map(function ($item) {
                return [
                    'id_misi'       => $item->id_misi,
                    'nama_misi'     => $item->nama_misi,
                    'deskripsi_misi'=> $item->deskripsi_misi,
                    'poin'          => $item->poin,
                    'status_misi'   => $item->status_misi,
                    'tipe_misi'     => $item->tipe_misi,
                    'admin'         => $item->admin->nama_admin ?? null, 
                ];
            }),
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

    // GET /api/user/misi/progress
    public function getUserProgress()
    {
        $user = Auth::user();
        
        // Ensure all active missions have a progress entry for this user
        $activeMissions = Misi::where('status_misi', 'aktif')->get();
        foreach ($activeMissions as $misi) {
            UserMisiProgress::firstOrCreate(
                ['id_user' => $user->id, 'id_misi' => $misi->id_misi],
                ['persentase' => 0, 'status' => 'aktif']
            );
        }

        $progressList = UserMisiProgress::with('misi')
            ->where('id_user', $user->id)
            ->get()
            ->filter(function ($progress) {
                return $progress->misi && $progress->misi->status_misi === 'aktif';
            })
            ->map(function ($progress) {
                return [
                    'id_progress'       => $progress->id_progress,
                    'id_misi'           => $progress->misi->id_misi,
                    'nama_misi'         => $progress->misi->nama_misi,
                    'deskripsi_misi'    => $progress->misi->deskripsi_misi,
                    'poin'              => $progress->misi->poin,
                    'tipe_misi'         => $progress->misi->tipe_misi,
                    'kondisi_parameter' => $progress->misi->kondisi_parameter,
                    'nilai_min'         => $progress->misi->nilai_min,
                    'nilai_max'         => $progress->misi->nilai_max,
                    'durasi_hari'       => $progress->misi->durasi_hari,
                    'hari_terpenuhi'    => $progress->hari_terpenuhi,
                    'nilai_terakhir'    => $progress->nilai_terakhir,
                    'persentase'        => $progress->persentase,
                    'status'            => $progress->status,
                    'selesai_at'        => $progress->selesai_at ? $progress->selesai_at->toIso8601String() : null,
                    'claimed_at'        => $progress->claimed_at ? $progress->claimed_at->toIso8601String() : null,
                    'bisa_diklaim'      => $progress->bisa_diklaim,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Progress misi berhasil diambil',
            'data'    => $progressList->values()
        ], 200);
    }

    // POST /api/user/misi/{id}/claim
    public function claimMisi($id)
    {
        $user = Auth::user();
        $progress = UserMisiProgress::where('id_user', $user->id)
            ->where('id_misi', $id)
            ->first();

        if (!$progress) {
            return response()->json(['success' => false, 'message' => 'Misi tidak ditemukan'], 404);
        }

        if ($progress->claimed_at) {
            return response()->json(['success' => false, 'message' => 'Misi sudah diklaim'], 400);
        }

        if (!$progress->bisa_diklaim && $progress->persentase < 100) {
            return response()->json(['success' => false, 'message' => 'Misi belum selesai'], 400);
        }

        $poinMisi = $progress->misi->poin;
        $user->coin += $poinMisi;
        $user->save();

        $progress->update([
            'claimed_at' => now(),
            'bisa_diklaim' => false,
            'status' => 'selesai'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Misi berhasil diklaim',
            'data' => [
                'poin_didapat' => $poinMisi,
                'total_poin' => $user->coin
            ]
        ], 200);
    }

    // POST /api/user/misi/{id}/reset
    public function resetProgress($id)
    {
        $user = Auth::user();
        $progress = UserMisiProgress::where('id_user', $user->id)
            ->where('id_misi', $id)
            ->first();

        if ($progress) {
            $progress->update([
                'hari_terpenuhi' => 0,
                'persentase' => 0,
                'status' => 'aktif',
                'selesai_at' => null,
                'claimed_at' => null,
                'bisa_diklaim' => false,
                'nilai_terakhir' => null
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Progress reset']);
    }

    // POST /api/user/misi/evaluate
    public function evaluateSensorProgress(Request $request)
    {
        $user = Auth::user();
        $sensorData = $request->all(); // e.g., ['ph' => 6.5, 'tds' => 800]
        
        $missions = Misi::where('status_misi', 'aktif')
            ->where('tipe_trigger', 'sensor_range')
            ->get();

        $updatedCount = 0;

        foreach ($missions as $misi) {
            $param = $misi->kondisi_parameter;
            if (!isset($sensorData[$param])) continue;

            $val = (float) $sensorData[$param];
            $progress = UserMisiProgress::firstOrCreate(
                ['id_user' => $user->id, 'id_misi' => $misi->id_misi],
                ['persentase' => 0, 'status' => 'aktif']
            );

            // Skip if already completed or claimed
            if ($progress->status === 'selesai' || $progress->bisa_diklaim) {
                continue;
            }

            $progress->nilai_terakhir = $val;

            // Check condition
            $conditionMet = true;
            if ($misi->nilai_min !== null && $val < $misi->nilai_min) $conditionMet = false;
            if ($misi->nilai_max !== null && $val > $misi->nilai_max) $conditionMet = false;

            if ($conditionMet) {
                $progress->hari_terpenuhi += 1; // Simplified: 1 sensor tick = 1 "day" or "progress unit"
                
                if ($progress->hari_terpenuhi >= $misi->durasi_hari) {
                    $progress->persentase = 100;
                    $progress->bisa_diklaim = true;
                    $progress->selesai_at = now();
                    $progress->status = 'selesai';
                } else {
                    $progress->persentase = (int) (($progress->hari_terpenuhi / $misi->durasi_hari) * 100);
                }
                $updatedCount++;
            }
            $progress->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Evaluated missions. $updatedCount updated."
        ], 200);
    }
}