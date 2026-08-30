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

        $oldStatus = $checklist->status;
        $newStatus = $validated['status'];

        $checklist->update($validated);

        if ($oldStatus !== $newStatus) {
            \App\Models\ChecklistHistory::create([
                'checklist_id' => $checklist->id,
                'status_lama' => $oldStatus,
                'status_baru' => $newStatus,
                'catatan' => $validated['catatan'] ?? null,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
            ]);
        }

        return redirect()->route('requests.show', $checklist->request_id)
            ->with('success', 'Checklist berhasil diperbarui.');
    }
}
