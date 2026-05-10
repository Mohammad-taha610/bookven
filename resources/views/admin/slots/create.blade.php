@extends('layouts.admin')

@section('title', 'Add weekly slots')

@section('content')
@php
    $oldDays = old('days', [1, 2, 3, 4, 5]);
    if (! is_array($oldDays)) {
        $oldDays = [1, 2, 3, 4, 5];
    }
    $oldWindows = old('windows', [['start' => '09:00', 'end' => '10:00'], ['start' => '18:00', 'end' => '19:00']]);
    if (! is_array($oldWindows) || count($oldWindows) === 0) {
        $oldWindows = [['start' => '09:00', 'end' => '10:00']];
    }
@endphp

<h1 class="h3 mb-2">Add weekly slots</h1>
<p class="text-muted small mb-4">Pick one court, choose which days repeat, and add one or more time windows. All combinations are created in one save (e.g. Mon–Fri × morning + evening = 10 slots).</p>

<form method="post" action="{{ route('admin.slots.store') }}" class="bg-white shadow-sm p-4 rounded" id="bulk-slot-form">
    @csrf
    <div class="mb-4">
        <label class="form-label fw-semibold">Court</label>
        <select name="court_id" class="form-select" required>
            @foreach($courts as $c)
                <option value="{{ $c->id }}" @selected(old('court_id', request('court_id')) == $c->id)>{{ $c->branch->name ?? 'Branch' }} — {{ $c->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="form-label fw-semibold mb-0">Days</span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Day presets">
                <button type="button" class="btn btn-outline-secondary" id="preset-all">All week</button>
                <button type="button" class="btn btn-outline-secondary" id="preset-weekdays">Mon–Fri</button>
                <button type="button" class="btn btn-outline-secondary" id="preset-weekend">Sat–Sun</button>
                <button type="button" class="btn btn-outline-secondary" id="preset-clear">Clear</button>
            </div>
        </div>
        <div class="row g-2" id="day-checkboxes">
            @foreach($dayNames as $num => $label)
                <div class="col-6 col-md-4 col-lg">
                    <div class="form-check border rounded px-3 py-2 h-100 bg-light">
                        <input class="form-check-input day-cb" type="checkbox" name="days[]" value="{{ $num }}" id="day-{{ $num }}"
                            @checked(in_array((string) $num, array_map('strval', $oldDays), true))>
                        <label class="form-check-label small" for="day-{{ $num }}">{{ $label }}</label>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="form-text">Uses your system convention: 0 = Sunday … 6 = Saturday.</div>
    </div>

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <label class="form-label fw-semibold mb-0">Time windows</label>
            <button type="button" class="btn btn-sm btn-outline-bv" id="add-window" style="border-color: var(--bv-green); color: var(--bv-green);">+ Add window</button>
        </div>
        <p class="text-muted small mb-2">Each row is one bookable block (e.g. 09:00–10:00). It is applied to every day you checked above.</p>
        <div id="windows-container" class="d-flex flex-column gap-2">
            @foreach($oldWindows as $idx => $row)
                <div class="window-row row g-2 align-items-end" data-index="{{ $idx }}">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0">Start</label>
                        <input type="time" class="form-control window-start" name="windows[{{ $idx }}][start]" value="{{ $row['start'] ?? '09:00' }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0">End</label>
                        <input type="time" class="form-control window-end" name="windows[{{ $idx }}][end]" value="{{ $row['end'] ?? '10:00' }}" required>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-window w-100" @if(count($oldWindows) < 2) style="visibility:hidden" @endif>Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-4 p-3 rounded border" style="background: var(--bv-green-light, #e8f5ec); border-color: #c5ddcc !important;">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="clear_selected_days" value="1" id="clear_selected_days"
                @checked(old('clear_selected_days'))>
            <label class="form-check-label small" for="clear_selected_days">
                <strong>Replace</strong> existing slots on this court for the <em>selected days only</em>, then add these windows.
                Leave unchecked to <strong>skip</strong> slots that already exist (same day + times).
            </label>
        </div>
    </div>

    <div class="alert alert-secondary py-2 mb-4 small mb-0" id="slot-preview" role="status">
        Select days and time windows to see how many slots will be created.
    </div>

    <button class="btn btn-bv" type="submit" id="submit-bulk">Create slots</button>
    <a href="{{ route('admin.slots.index') }}" class="btn btn-link">Cancel</a>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('bulk-slot-form');
    if (!form) return;

    const container = document.getElementById('windows-container');
    let nextIndex = {{ count($oldWindows) }};

    function selectedDaysCount() {
        return form.querySelectorAll('.day-cb:checked').length;
    }

    function windowRowsCount() {
        return container.querySelectorAll('.window-row').length;
    }

    function updatePreview() {
        const d = selectedDaysCount();
        const w = windowRowsCount();
        const n = d * w;
        const el = document.getElementById('slot-preview');
        if (d === 0 || w === 0) {
            el.textContent = 'Select at least one day and keep at least one time window.';
            el.className = 'alert alert-warning py-2 mb-4 small mb-0';
            return;
        }
        el.textContent = 'Will create ' + n + ' slot' + (n === 1 ? '' : 's') + ' (' + d + ' day' + (d === 1 ? '' : 's') + ' × ' + w + ' window' + (w === 1 ? '' : 's') + '). Duplicates are skipped unless you enable Replace.';
        el.className = 'alert alert-secondary py-2 mb-4 small mb-0';
    }

    function refreshRemoveButtons() {
        const rows = container.querySelectorAll('.window-row');
        rows.forEach(function (row) {
            const btn = row.querySelector('.remove-window');
            btn.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
        });
    }

    function reindexWindowNames() {
        container.querySelectorAll('.window-row').forEach(function (row, i) {
            row.dataset.index = String(i);
            row.querySelector('.window-start').name = 'windows[' + i + '][start]';
            row.querySelector('.window-end').name = 'windows[' + i + '][end]';
        });
        nextIndex = container.querySelectorAll('.window-row').length;
    }

    document.getElementById('add-window').addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'window-row row g-2 align-items-end';
        row.dataset.index = String(nextIndex);
        row.innerHTML =
            '<div class="col-md-4">' +
            '<label class="form-label small text-muted mb-0">Start</label>' +
            '<input type="time" class="form-control window-start" name="windows[' + nextIndex + '][start]" value="09:00" required>' +
            '</div>' +
            '<div class="col-md-4">' +
            '<label class="form-label small text-muted mb-0">End</label>' +
            '<input type="time" class="form-control window-end" name="windows[' + nextIndex + '][end]" value="10:00" required>' +
            '</div>' +
            '<div class="col-md-4">' +
            '<button type="button" class="btn btn-outline-danger btn-sm remove-window w-100">Remove</button>' +
            '</div>';
        container.appendChild(row);
        nextIndex++;
        refreshRemoveButtons();
        updatePreview();
        row.querySelector('.remove-window').addEventListener('click', onRemove);
        row.querySelector('.window-start').addEventListener('change', updatePreview);
        row.querySelector('.window-end').addEventListener('change', updatePreview);
    });

    function onRemove() {
        const row = this.closest('.window-row');
        if (container.querySelectorAll('.window-row').length <= 1) return;
        row.remove();
        reindexWindowNames();
        refreshRemoveButtons();
        updatePreview();
    }

    container.querySelectorAll('.remove-window').forEach(function (btn) {
        btn.addEventListener('click', onRemove);
    });

    form.querySelectorAll('.day-cb').forEach(function (cb) {
        cb.addEventListener('change', updatePreview);
    });
    container.querySelectorAll('.window-start, .window-end').forEach(function (inp) {
        inp.addEventListener('change', updatePreview);
    });

    document.getElementById('preset-all').addEventListener('click', function () {
        form.querySelectorAll('.day-cb').forEach(function (cb) { cb.checked = true; });
        updatePreview();
    });
    document.getElementById('preset-weekdays').addEventListener('click', function () {
        form.querySelectorAll('.day-cb').forEach(function (cb) {
            const v = parseInt(cb.value, 10);
            cb.checked = v >= 1 && v <= 5;
        });
        updatePreview();
    });
    document.getElementById('preset-weekend').addEventListener('click', function () {
        form.querySelectorAll('.day-cb').forEach(function (cb) {
            const v = parseInt(cb.value, 10);
            cb.checked = v === 0 || v === 6;
        });
        updatePreview();
    });
    document.getElementById('preset-clear').addEventListener('click', function () {
        form.querySelectorAll('.day-cb').forEach(function (cb) { cb.checked = false; });
        updatePreview();
    });

    form.addEventListener('submit', function (e) {
        if (selectedDaysCount() === 0) {
            e.preventDefault();
            alert('Select at least one day of the week.');
            return;
        }
    });

    updatePreview();
})();
</script>
@endpush
