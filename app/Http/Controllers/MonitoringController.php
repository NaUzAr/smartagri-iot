<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    /**
     * Halaman utama monitoring - list device user
     */
    public function index()
    {
        $userDevices = UserDevice::with(['device.sensors'])
            ->where('user_id', Auth::id())
            ->get();

        return view('monitoring.index', compact('userDevices'));
    }

    /**
     * Form tambah device via token
     */
    public function create()
    {
        return view('monitoring.add_device');
    }

    /**
     * Proses tambah device via token
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:16',
            'custom_name' => 'nullable|string|max:100',
        ], [
            'token.required' => 'Token wajib diisi!',
            'token.size' => 'Token harus 16 karakter!',
        ]);

        // Cari device berdasarkan token
        $device = Device::where('token', $request->token)->first();

        if (!$device) {
            return back()->withErrors(['token' => 'Token tidak ditemukan! Pastikan token benar.'])->withInput();
        }

        // Cek apakah user sudah punya device ini
        $exists = UserDevice::where('user_id', Auth::id())
            ->where('device_id', $device->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['token' => 'Device ini sudah ada di daftar monitoring Anda.'])->withInput();
        }

        // Simpan ke user_devices
        UserDevice::create([
            'user_id' => Auth::id(),
            'device_id' => $device->id,
            'custom_name' => $request->custom_name ?: $device->name,
        ]);

        return redirect()->route('monitoring.index')
            ->with('success', "Device '{$device->name}' berhasil ditambahkan ke monitoring!");
    }

    /**
     * Halaman monitoring device - tampilkan data sensor
     */
    public function show(Request $request, $id)
    {
        // Pastikan user punya akses ke device ini
        $userDevice = UserDevice::with(['device.sensors'])
            ->where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $device = $userDevice->device;
        $sensors = $device->sensors;

        // Default values
        $logData = collect();
        $chartData = collect();
        $latestData = null;

        if ($device->table_name && \Schema::hasTable($device->table_name)) {
            // Ambil 50 data terbaru untuk chart (tidak di-paginate)
            $chartData = DB::table($device->table_name)
                ->orderBy('recorded_at', 'desc')
                ->limit(50)
                ->get()
                ->reverse()
                ->values();

            // Ambil data untuk tabel dengan pagination (20 per halaman)
            $logData = DB::table($device->table_name)
                ->orderBy('recorded_at', 'desc')
                ->paginate(20);

            // Ambil data terbaru untuk display sensor cards
            $latestData = DB::table($device->table_name)
                ->orderBy('recorded_at', 'desc')
                ->first();
        } else {
            // Buat paginator kosong jika tidak ada data
            $logData = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        return view('monitoring.show', compact('userDevice', 'device', 'sensors', 'logData', 'chartData', 'latestData'));
    }

    /**
     * Hapus device dari monitoring user
     */
    public function destroy($id)
    {
        $userDevice = UserDevice::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $deviceName = $userDevice->custom_name;
        $userDevice->delete();

        return redirect()->route('monitoring.index')
            ->with('success', "Device '{$deviceName}' berhasil dihapus dari monitoring.");
    }
}
