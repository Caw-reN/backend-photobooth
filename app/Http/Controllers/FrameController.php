<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Frame;

class FrameController extends Controller
{
    public function index() 
    {
        $frames = Frame::latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $frames
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|mimes:png|max:5120',
            'coordinates' => 'required', 
        ]);

        try {
            $rawCoordinates = $request->input('coordinates');
            $decodedCoordinates = json_decode($rawCoordinates, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Format JSON Koordinat tidak valid: ' . json_last_error_msg()
                ], 422);
            }

            // 3. Upload file
            $imagePath = $request->file('image')->store('frames', 'public');

            // 4. Simpan ke Database
            $frame = Frame::create([
                'name' => $request->name,
                'image_path' => $imagePath,
                'coordinates' => $decodedCoordinates, 
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Frame berhasil ditambahkan!',
                'data' => $frame
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan frame baru: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $frame = Frame::findOrFail($id);

            // Hapus gambar fisik jika ada
            if ($frame->image_path && Storage::disk('public')->exists($frame->image_path)) {
                Storage::disk('public')->delete($frame->image_path);
            }
            
            $frame->delete();

            return response()->json([
                'status' => 'success', 
                'message' => 'Frame berhasil dihapus!'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal dari Laravel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
{
    // 1. Validasi data
    $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'image' => 'nullable|image|mimes:png|max:5120', 
        'coordinates' => 'sometimes|required',
    ]);

    try {
        $frame = Frame::findOrFail($id);

        // 2. Kemaskini nama & status aktif
        if ($request->has('name')) $frame->name = $request->name;
        if ($request->has('is_active')) $frame->is_active = $request->is_active == '1';

        // 3. Kemaskini koordinat
        if ($request->has('coordinates')) {
            $frame->coordinates = json_decode($request->coordinates, true);
        }

        // 4. Jika ada gambar baru diupload
        if ($request->hasFile('image')) {
            // Padam gambar lama dari storage
            if ($frame->image_path && \Storage::disk('public')->exists($frame->image_path)) {
                \Storage::disk('public')->delete($frame->image_path);
            }
            // Simpan gambar baru
            $path = $request->file('image')->store('frames', 'public');
            $frame->image_path = $path;
        }

        $frame->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Frame berjaya dikemaskini!',
            'data' => $frame
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}
}
