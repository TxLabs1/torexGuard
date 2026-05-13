{{-- quarantine/index.blade.php --}}
@extends('layouts.app')
@section('title','Quarantine')
@section('page-title','Quarantine Vault')

@section('content')
<div class="tg-card">
    <div class="tg-card-header">
        <i class="fas fa-vault text-medium" style="font-size:13px"></i>
        <div class="tg-card-title">Quarantined Files</div>
        <span class="tg-badge badge-medium">{{ $files->total() }} Files</span>
    </div>
    <table class="tg-table">
        <thead>
            <tr><th>ORIGINAL PATH</th><th>PROJECT</th><th>SERVER</th><th>SIZE</th><th>STATUS</th><th>QUARANTINED</th><th>ACTIONS</th></tr>
        </thead>
        <tbody>
            @forelse($files as $file)
            <tr>
                <td>
                    <div class="mono" style="font-size:12px;color:var(--tg-critical)">{{ $file->original_path }}</div>
                    <div class="mono" style="font-size:11px;color:var(--tg-text-muted)">{{ Str::limit($file->file_hash, 20) }}...</div>
                </td>
                <td style="font-size:13px">{{ $file->project->name ?? '—' }}</td>
                <td style="font-size:13px">{{ $file->server->hostname ?? '—' }}</td>
                <td class="mono" style="font-size:12px">{{ round($file->file_size/1024,1) }} KB</td>
                <td>
                    @php $statusColors = ['quarantined'=>'badge-high','restored'=>'badge-success','deleted'=>'badge-critical'] @endphp
                    <span class="tg-badge {{ $statusColors[$file->status] ?? '' }}">{{ strtoupper($file->status) }}</span>
                </td>
                <td style="font-size:12px;color:var(--tg-text-muted)">{{ $file->quarantined_at->format('Y-m-d H:i') }}</td>
                <td>
                    @if($file->status === 'quarantined')
                    <button onclick="qAction('restore','{{ $file->id }}')" class="btn-tg-ghost" style="padding:4px 10px;font-size:11px">
                        <i class="fas fa-rotate-left"></i> Restore
                    </button>
                    <button onclick="qAction('delete','{{ $file->id }}')" class="btn-tg-danger" style="padding:4px 10px;font-size:11px">
                        <i class="fas fa-trash"></i>
                    </button>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7">
                <div class="text-center py-5">
                    <i class="fas fa-vault" style="font-size:40px;color:var(--tg-accent);opacity:0.3;display:block;margin-bottom:12px"></i>
                    <div style="color:var(--tg-text-muted)">Quarantine vault is empty</div>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($files->hasPages())
    <div class="tg-card-body" style="border-top:1px solid var(--tg-border)">{{ $files->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
async function qAction(action, fileId) {
    const msg = action === 'restore' ? 'Restore file to original location?' : 'Permanently delete this file? This cannot be undone.';
    if (!confirm(msg)) return;
    const method = action === 'delete' ? 'DELETE' : 'POST';
    const url = action === 'delete' ? `/quarantine/${fileId}` : `/quarantine/${fileId}/restore`;
    const r = await fetch(url, { method, headers: { 'X-CSRF-TOKEN': CSRF } });
    const d = await r.json();
    if (d.success) { showToast('Done', d.message, 'success'); setTimeout(() => location.reload(), 1200); }
}
</script>
@endpush
