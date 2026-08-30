<?php

namespace App\Http\Controllers;

use App\Models\SpjChecklist;
use Illuminate\Http\Request;

class SpjChecklistController extends Controller
{
    public function edit($id)
    {
        $checklist = SpjChecklist::findOrFail($id);
        return view('checklists.edit', compact('checklist'));
    }

    public function update(Request $request, $id)
    {
        $checklist = SpjChecklist::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:Belum Ada,Belum Lengkap,Lengkap,Perlu Perbaikan',
            'catatan' => 'nullable|string',
        ]);

        $checklist->update($validated);

        return redirect()->route('requests.show', $checklist->request_id)
            ->with('success', 'Checklist berhasil diperbarui.');
    }
}
