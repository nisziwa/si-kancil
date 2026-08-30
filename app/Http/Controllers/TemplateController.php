<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = Template::query();

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('search')) {
            $query->where('nama_template', 'like', '%' . $request->search . '%');
        }

        $templates = $query->orderBy('kategori')->orderBy('nama_template')->paginate(15)->withQueryString();
        $kategoriList = Template::KATEGORI_LIST;

        return view('templates.index', compact('templates', 'kategoriList'));
    }

    public function create()
    {
        $kategoriList = Template::KATEGORI_LIST;
        return view('templates.create', compact('kategoriList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_template' => 'required|string|max:255',
            'kategori' => 'required|in:' . implode(',', Template::KATEGORI_LIST),
            'versi' => 'nullable|string|max:50',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip|max:20480',
            'status_aktif' => 'nullable|boolean',
        ]);

        $filePath = $request->file('file')->store('templates', 'public');

        Template::create([
            'nama_template' => $validated['nama_template'],
            'kategori' => $validated['kategori'],
            'versi' => $validated['versi'] ?? 'v1.0',
            'file' => $filePath,
            'status_aktif' => $request->boolean('status_aktif', true),
        ]);

        return redirect()->route('templates.index')
            ->with('success', 'Template dokumen berhasil ditambahkan ke repository.');
    }

    public function edit($id)
    {
        $template = Template::findOrFail($id);
        $kategoriList = Template::KATEGORI_LIST;

        return view('templates.edit', compact('template', 'kategoriList'));
    }

    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'nama_template' => 'required|string|max:255',
            'kategori' => 'required|in:' . implode(',', Template::KATEGORI_LIST),
            'versi' => 'nullable|string|max:50',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip|max:20480',
            'status_aktif' => 'nullable|boolean',
        ]);

        if ($request->hasFile('file')) {
            if ($template->file && Storage::disk('public')->exists($template->file)) {
                Storage::disk('public')->delete($template->file);
            }
            $template->file = $request->file('file')->store('templates', 'public');
        }

        $template->nama_template = $validated['nama_template'];
        $template->kategori = $validated['kategori'];
        $template->versi = $validated['versi'] ?? $template->versi;
        $template->status_aktif = $request->boolean('status_aktif', true);
        $template->save();

        return redirect()->route('templates.index')
            ->with('success', 'Template dokumen berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $template = Template::findOrFail($id);

        if ($template->file && Storage::disk('public')->exists($template->file)) {
            Storage::disk('public')->delete($template->file);
        }

        $template->delete();

        return redirect()->route('templates.index')
            ->with('success', 'Template berhasil dihapus.');
    }

    public function download($id)
    {
        $template = Template::findOrFail($id);

        if (!$template->file || !Storage::disk('public')->exists($template->file)) {
            return back()->with('error', 'File template tidak ditemukan.');
        }

        return Storage::disk('public')->download($template->file);
    }
}
