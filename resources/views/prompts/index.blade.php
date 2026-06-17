@extends('layouts.app')

@section('title', 'Prompts')
@section('page_header', 'Prompts')
@section('page_icon', 'mdi mdi-message-text-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active">Prompts</li>
@endsection

@php
    use Illuminate\Support\Str;

    // Icon per group so the list reads at a glance.
    $groupIcons = [
        'Post Creator'         => 'mdi-pencil-box-multiple-outline',
        'AI Studio'            => 'mdi-robot-outline',
        'Content Tools'        => 'mdi-toolbox-outline',
        'Growth Intelligence'  => 'mdi-chart-line',
        'general'              => 'mdi-message-text-outline',
    ];

    $allPrompts  = $groups->flatten();
    $totalCount  = $allPrompts->count();
    $activeCount = $allPrompts->where('is_active', true)->count();
    $firstId     = optional($allPrompts->first())->id;
@endphp

@push('styles')
<style>
    :root {
        --pp-border: #e6e9ef;
        --pp-muted: #64748b;
        --pp-accent: #2563eb;
        --pp-bg-soft: #f8fafc;
    }

    /* ---- Stat strip ---- */
    .pp-stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .pp-stat {
        flex: 1 1 0; min-width: 150px; background: #fff; border: 1px solid var(--pp-border);
        border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; gap: 14px;
    }
    .pp-stat .pp-ic {
        width: 42px; height: 42px; border-radius: 11px; display: grid; place-items: center;
        font-size: 22px; background: #eff4ff; color: var(--pp-accent);
    }
    .pp-stat .pp-num { font-size: 22px; font-weight: 700; line-height: 1; color: #0f172a; }
    .pp-stat .pp-cap { font-size: 12px; color: var(--pp-muted); }

    /* ---- Layout ---- */
    .pp-shell { display: grid; grid-template-columns: 340px 1fr; gap: 18px; align-items: start; }
    @media (max-width: 991px) { .pp-shell { grid-template-columns: 1fr; } }

    /* ---- List panel ---- */
    .pp-list-card {
        background: #fff; border: 1px solid var(--pp-border); border-radius: 16px; overflow: hidden;
        position: sticky; top: 16px;
    }
    .pp-search { padding: 12px; border-bottom: 1px solid var(--pp-border); }
    .pp-search .input-group-text { background: var(--pp-bg-soft); border-right: 0; color: var(--pp-muted); }
    .pp-search .form-control { border-left: 0; box-shadow: none; }
    .pp-list { max-height: 70vh; overflow-y: auto; padding: 6px; }
    .pp-group-h {
        font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        color: var(--pp-muted); padding: 12px 12px 6px; display: flex; align-items: center; gap: 7px;
    }
    .pp-item {
        display: flex; align-items: center; gap: 10px; width: 100%; text-align: left;
        border: 0; background: transparent; border-radius: 10px; padding: 9px 11px; cursor: pointer;
        color: #1e293b; transition: background .12s;
    }
    .pp-item:hover { background: var(--pp-bg-soft); }
    .pp-item.active { background: #eff4ff; }
    .pp-item.active .pp-item-title { color: var(--pp-accent); font-weight: 600; }
    .pp-item-ic { color: #94a3b8; font-size: 18px; flex: 0 0 auto; }
    .pp-item.active .pp-item-ic { color: var(--pp-accent); }
    .pp-item-title { font-size: 13.5px; line-height: 1.25; }
    .pp-dot { width: 7px; height: 7px; border-radius: 50%; flex: 0 0 auto; margin-left: auto; }
    .pp-dot.on { background: #22c55e; }
    .pp-dot.off { background: #cbd5e1; }
    .pp-empty { padding: 24px 14px; text-align: center; color: var(--pp-muted); font-size: 13px; display: none; }

    /* ---- Editor panel ---- */
    .pp-editor { display: none; }
    .pp-editor.active { display: block; }
    .pp-edit-card { background: #fff; border: 1px solid var(--pp-border); border-radius: 16px; }
    .pp-edit-head {
        display: flex; align-items: flex-start; gap: 14px; padding: 18px 20px; border-bottom: 1px solid var(--pp-border);
    }
    .pp-edit-head .pp-ic {
        width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
        font-size: 23px; background: #eff4ff; color: var(--pp-accent); flex: 0 0 auto;
    }
    .pp-edit-title { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0; }
    .pp-key-pill {
        font-family: ui-monospace, monospace; font-size: 11px; color: var(--pp-muted);
        background: var(--pp-bg-soft); border: 1px solid var(--pp-border); border-radius: 6px;
        padding: 1px 7px; display: inline-block; margin-top: 3px;
    }
    .pp-edit-body { padding: 20px; }
    .pp-label { font-size: 12px; font-weight: 600; color: #334155; text-transform: uppercase; letter-spacing: .03em; }

    .prompt-template {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12.8px; line-height: 1.6; min-height: 360px; tab-size: 2;
        white-space: pre; background: #fbfcfe; border-radius: 12px;
    }
    .prompt-template:focus { background: #fff; }
    .pp-meta-row { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; }
    .pp-charcount { font-size: 11px; color: var(--pp-muted); }

    .var-chip {
        font-family: ui-monospace, monospace; font-size: 11.5px; cursor: pointer;
        background: #fff; border: 1px dashed #c7d2fe; color: #4338ca; border-radius: 8px;
        padding: 4px 9px; transition: all .12s;
    }
    .var-chip:hover { background: #eef2ff; border-style: solid; }
    .var-chip:active { transform: translateY(1px); }

    .pp-actions {
        display: flex; gap: 10px; align-items: center; padding: 14px 20px;
        border-top: 1px solid var(--pp-border); background: var(--pp-bg-soft);
        border-radius: 0 0 16px 16px; position: sticky; bottom: 0;
    }
    .form-switch .form-check-input { cursor: pointer; }
</style>
@endpush

@section('content')

@if (session('status'))
    <div class="alert alert-success d-flex align-items-center border-0 shadow-sm" role="alert">
        <i class="mdi mdi-check-circle-outline fs-4 me-2"></i>
        <div>{{ session('status') }}</div>
    </div>
@endif

{{-- ── Stat strip ── --}}
<div class="pp-stats">
    <div class="pp-stat">
        <span class="pp-ic"><i class="mdi mdi-message-text-outline"></i></span>
        <div><div class="pp-num">{{ $totalCount }}</div><div class="pp-cap">Total prompts</div></div>
    </div>
    <div class="pp-stat">
        <span class="pp-ic" style="background:#ecfdf5;color:#16a34a;"><i class="mdi mdi-check-circle-outline"></i></span>
        <div><div class="pp-num">{{ $activeCount }}</div><div class="pp-cap">Active</div></div>
    </div>
    <div class="pp-stat">
        <span class="pp-ic" style="background:#fef3c7;color:#d97706;"><i class="mdi mdi-folder-multiple-outline"></i></span>
        <div><div class="pp-num">{{ $groups->count() }}</div><div class="pp-cap">Categories</div></div>
    </div>
</div>

<div class="alert alert-info d-flex align-items-start border-0" role="alert" style="background:#eff6ff;color:#1e40af;">
    <i class="mdi mdi-information-outline fs-5 me-2"></i>
    <div class="small">
        These are the live AI prompts the app sends to Gemini. Pick one on the left, edit the wording, and
        <strong>Save</strong> — it applies everywhere instantly. Keep the
        <code>&#123;&#123; token &#125;&#125;</code> placeholders; click a token chip to drop it into the prompt at your cursor.
    </div>
</div>

<div class="pp-shell">

    {{-- ════════ LEFT: searchable list ════════ --}}
    <div class="pp-list-card">
        <div class="pp-search">
            <div class="input-group">
                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                <input type="text" id="pp-search" class="form-control" placeholder="Search prompts…" autocomplete="off">
            </div>
        </div>
        <div class="pp-list" id="pp-list">
            @foreach ($groups as $groupName => $prompts)
                <div class="pp-group" data-group="{{ $groupName }}">
                    <div class="pp-group-h">
                        <i class="mdi {{ $groupIcons[$groupName] ?? $groupIcons['general'] }}"></i>
                        {{ $groupName }}
                    </div>
                    @foreach ($prompts as $prompt)
                        <button type="button" class="pp-item" data-target="ed-{{ $prompt->id }}"
                                data-search="{{ Str::lower($prompt->name.' '.$prompt->description.' '.$prompt->key) }}">
                            <i class="mdi {{ $groupIcons[$groupName] ?? $groupIcons['general'] }} pp-item-ic"></i>
                            <span class="pp-item-title">{{ $prompt->name }}</span>
                            <span class="pp-dot {{ $prompt->is_active ? 'on' : 'off' }}"
                                  title="{{ $prompt->is_active ? 'Active' : 'Disabled' }}"></span>
                        </button>
                    @endforeach
                </div>
            @endforeach
            <div class="pp-empty" id="pp-empty">No prompts match “<span id="pp-empty-q"></span>”.</div>
        </div>
    </div>

    {{-- ════════ RIGHT: editor panels ════════ --}}
    <div id="pp-editors">
        @forelse ($allPrompts as $prompt)
            @php $gName = $prompt->group ?: 'general'; @endphp
            <div class="pp-editor {{ $prompt->id === $firstId ? 'active' : '' }}" id="ed-{{ $prompt->id }}">
                <form method="POST" action="{{ route('prompts.update', $prompt) }}" class="pp-edit-card">
                    @csrf
                    @method('PUT')

                    <div class="pp-edit-head">
                        <span class="pp-ic"><i class="mdi {{ $groupIcons[$gName] ?? $groupIcons['general'] }}"></i></span>
                        <div class="flex-grow-1">
                            <h2 class="pp-edit-title">{{ $prompt->name }}</h2>
                            <span class="pp-key-pill">{{ $gName }} · key: {{ $prompt->key }}</span>
                        </div>
                        <div class="form-check form-switch m-0 mt-1">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="active-{{ $prompt->id }}" name="is_active" value="1"
                                   {{ $prompt->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small" for="active-{{ $prompt->id }}">Active</label>
                        </div>
                    </div>

                    <div class="pp-edit-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label pp-label">Name</label>
                                <input type="text" name="name" class="form-control"
                                       value="{{ old('name', $prompt->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label pp-label">Description</label>
                                <input type="text" name="description" class="form-control"
                                       value="{{ old('description', $prompt->description) }}"
                                       placeholder="Where this prompt is used">
                            </div>

                            <div class="col-12">
                                <label class="form-label pp-label mb-1">Prompt template</label>
                                <textarea name="template" class="form-control prompt-template" spellcheck="false" required>{{ old('template', $prompt->template) }}</textarea>
                                <div class="pp-meta-row">
                                    <span class="text-muted small">
                                        <i class="mdi mdi-information-outline"></i>
                                        Placeholders like <code>&#123;&#123; token &#125;&#125;</code> are filled in at runtime.
                                    </span>
                                    <span class="pp-charcount"><span class="pp-len">{{ Str::length($prompt->template) }}</span> chars</span>
                                </div>
                            </div>

                            @if (!empty($prompt->variables))
                                <div class="col-12">
                                    <label class="form-label pp-label mb-2">Inputs available — click to insert</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($prompt->variables as $var)
                                            <button type="button" class="var-chip"
                                                    data-token="&#123;&#123; {{ $var['name'] }} &#125;&#125;"
                                                    title="{{ $var['description'] ?? '' }}">
                                                &#123;&#123; {{ $var['name'] }} &#125;&#125;
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pp-actions">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="mdi mdi-content-save"></i> Save changes
                        </button>
                        <button type="submit" class="btn btn-light border ms-auto"
                                formaction="{{ route('prompts.reset', $prompt) }}"
                                onclick="return confirm('Reset “{{ $prompt->name }}” to its packaged default? Your edits will be lost.');">
                            <i class="mdi mdi-backup-restore"></i> Reset to default
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <div class="alert alert-warning">No prompts found.</div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
(function () {
    const items   = Array.from(document.querySelectorAll('.pp-item'));
    const editors = document.getElementById('pp-editors');

    // ── Select a prompt ──
    function select(targetId) {
        items.forEach(i => i.classList.toggle('active', i.dataset.target === targetId));
        editors.querySelectorAll('.pp-editor').forEach(ed =>
            ed.classList.toggle('active', ed.id === targetId));
        if (window.innerWidth < 992) {
            editors.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    items.forEach(i => i.addEventListener('click', () => select(i.dataset.target)));
    if (items[0]) items[0].classList.add('active');

    // ── Live search ──
    const search  = document.getElementById('pp-search');
    const empty   = document.getElementById('pp-empty');
    const emptyQ  = document.getElementById('pp-empty-q');
    search.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        let shown = 0;
        items.forEach(i => {
            const hit = !q || i.dataset.search.includes(q);
            i.style.display = hit ? '' : 'none';
            if (hit) shown++;
        });
        // Hide group headers with no visible items
        document.querySelectorAll('.pp-group').forEach(g => {
            const any = Array.from(g.querySelectorAll('.pp-item')).some(i => i.style.display !== 'none');
            g.style.display = any ? '' : 'none';
        });
        empty.style.display = shown ? 'none' : 'block';
        emptyQ.textContent = q;
    });

    // ── Click-to-insert token chips ──
    document.querySelectorAll('.var-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            const ta = this.closest('.pp-edit-card').querySelector('.prompt-template');
            const token = this.dataset.token;
            const s = ta.selectionStart ?? ta.value.length;
            const e = ta.selectionEnd ?? ta.value.length;
            ta.value = ta.value.slice(0, s) + token + ta.value.slice(e);
            const pos = s + token.length;
            ta.focus();
            ta.setSelectionRange(pos, pos);
            ta.dispatchEvent(new Event('input'));
        });
    });

    // ── Live char count ──
    document.querySelectorAll('.prompt-template').forEach(ta => {
        const counter = ta.closest('.pp-edit-card').querySelector('.pp-len');
        ta.addEventListener('input', () => { if (counter) counter.textContent = ta.value.length; });
    });
})();
</script>
@endpush

@endsection
