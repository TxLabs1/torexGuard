<?php

namespace App\Http\Controllers;

use App\Models\QuarantineFile;

class QuarantineController extends Controller
{
    public function index()
    {
        // Filter via the project relation, which itself is org-scoped.
        $files = QuarantineFile::with(['threat', 'project', 'server', 'quarantinedBy'])
            ->whereHas('project') // org scope on Project filters here automatically
            ->latest('quarantined_at')
            ->paginate(20);

        return view('quarantine.index', compact('files'));
    }

    public function restore(QuarantineFile $file)
    {
        $this->ensureOwned($file);
        $file->update(['status' => 'restored', 'restored_at' => now()]);

        return response()->json(['success' => true, 'message' => 'File restoration queued']);
    }

    public function destroy(QuarantineFile $file)
    {
        $this->ensureOwned($file);
        $file->update(['status' => 'deleted']);

        return response()->json(['success' => true, 'message' => 'File permanently deleted']);
    }

    private function ensureOwned(QuarantineFile $file): void
    {
        // Force a fresh org-scoped lookup; if the user's org doesn't own
        // the parent project, this returns null.
        $owned = QuarantineFile::whereHas('project')
            ->where('id', $file->id)
            ->exists();

        abort_unless($owned, 403);
    }
}
