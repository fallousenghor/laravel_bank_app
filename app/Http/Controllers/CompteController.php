<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;

class CompteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Compte::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'solde' => 'required|numeric',
            'statut' => 'required|string',
            'date_creation' => 'required|date',
            'utilisateur_id' => 'required|string'
        ]);

        return Compte::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Compte::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $compte = Compte::findOrFail($id);
        $validated = $request->validate([
            'type' => 'string',
            'solde' => 'numeric',
            'statut' => 'string',
            'date_creation' => 'date',
            'utilisateur_id' => 'string'
        ]);

        $compte->update($validated);
        return $compte;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $compte = Compte::findOrFail($id);
        $compte->delete();
        return response()->json(null, 204);
    }
}
