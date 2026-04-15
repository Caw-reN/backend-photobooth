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
}
