@extends('layouts.admin')

@section('title', 'Edit slot')

@section('content')
<h1 class="h3 mb-3">Edit slot</h1>
<form method="post" action="{{ route('admin.slots.update', $slot) }}" class="bg-white shadow-sm p-4 rounded">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Court</label>
        <select name="court_id" class="form-select" required>
            @foreach($courts as $c)
                <option value="{{ $c->id }}" @selected(old('court_id', $slot->court_id) == $c->id)>{{ $c->branch->name ?? 'Branch' }} — {{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Day of week</label>
        <select name="day_of_week" class="form-select" required>
            @foreach($dayNames as $num => $label)
                <option value="{{ $num }}" @selected((int) old('day_of_week', $slot->day_of_week) === $num)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Start time</label>
            <input name="start_time" type="time" class="form-control" value="{{ old('start_time', \Illuminate\Support\Str::substr($slot->start_time, 0, 5)) }}" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">End time</label>
            <input name="end_time" type="time" class="form-control" value="{{ old('end_time', \Illuminate\Support\Str::substr($slot->end_time, 0, 5)) }}" required>
        </div>
    </div>
    <button class="btn btn-bv" type="submit">Update</button>
    <a href="{{ route('admin.slots.index') }}" class="btn btn-link">Back</a>
</form>
@endsection
