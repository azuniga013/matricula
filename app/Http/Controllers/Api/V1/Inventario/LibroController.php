<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => 'nullable|in:activo,inactivo',
        ]);

        $query = Libro::with('niveles:id,codigo,nombre');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('libros.codigo', 'like', "%{$buscar}%")
                  ->orWhere('libros.titulo', 'like', "%{$buscar}%")
                  ->orWhere('libros.autor', 'like', "%{$buscar}%")
                  ->orWhere('libros.isbn', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('libros.estado', $request->estado);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $query->orderBy('libros.codigo')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:libros,codigo',
            'titulo' => 'required|string|max:255',
            'autor' => 'nullable|string|max:255',
            'editorial' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:libros,isbn',
            'precio_venta' => 'required|numeric|min:0',
            'nivel_ids' => 'nullable|array',
            'nivel_ids.*' => 'exists:niveles_academicos,id',
        ]);

        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();

        $libro = Libro::create($datos);

        if (!empty($datos['nivel_ids'])) {
            $syncData = [];
            foreach ($datos['nivel_ids'] as $nivelId) {
                $syncData[$nivelId] = ['creado_por' => $request->user()->id];
            }
            $libro->niveles()->sync($syncData);
        }

        $libro->load('niveles:id,codigo,nombre');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Libro creado exitosamente',
            'data' => $libro,
        ], 201);
    }

    public function show(Libro $libro): JsonResponse
    {
        $libro->load('niveles:id,codigo,nombre');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $libro,
        ]);
    }

    public function update(Request $request, Libro $libro): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => "required|string|max:50|unique:libros,codigo,{$libro->id}",
            'titulo' => 'required|string|max:255',
            'autor' => 'nullable|string|max:255',
            'editorial' => 'nullable|string|max:255',
            'isbn' => "nullable|string|max:20|unique:libros,isbn,{$libro->id}",
            'precio_venta' => 'required|numeric|min:0',
            'estado' => 'nullable|in:activo,inactivo',
            'nivel_ids' => 'nullable|array',
            'nivel_ids.*' => 'exists:niveles_academicos,id',
        ]);

        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $libro->update($datos);

        if (array_key_exists('nivel_ids', $datos)) {
            $syncData = [];
            foreach ($datos['nivel_ids'] as $nivelId) {
                $syncData[$nivelId] = ['actualizado_por' => $request->user()->id];
            }
            $libro->niveles()->sync($syncData);
        }

        $libro->load('niveles:id,codigo,nombre');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Libro actualizado exitosamente',
            'data' => $libro,
        ]);
    }
}
