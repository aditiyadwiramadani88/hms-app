@php
    $vehiclesInside = $vehiclesInside ?? collect();
@endphp

<div class="p-2 border-bottom">
    <input type="text" class="form-control form-control-sm" id="searchInside" placeholder="Cari plat nomor..." onkeyup="filterInsideList(this.value)">
</div>
<div id="insideList">
    @forelse($vehiclesInside as $log)
    <div class="sg-list-item inside-item" data-plate="{{ strtolower($log->plate_number) }}" data-driver="{{ strtolower($log->driver_name) }}">
        <div class="sg-vehicle-info">
            <div class="plate">{{ $log->plate_number }}</div>
            <div class="meta">
                {{ $log->driver_name }}
                @if($log->destination_room) &middot; R.{{ $log->destination_room }} @endif
                &middot; {{ $log->time_in->format('H:i') }}
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($log->photo_in)
                @php
                    $photos = is_array($log->photo_in) ? $log->photo_in : [$log->photo_in];
                    $firstPhoto = $photos[0] ?? null;
                @endphp
                @if($firstPhoto)
                    <div class="position-relative">
                        <img src="{{ asset('storage/' . $firstPhoto) }}" class="rounded" style="width:40px;height:40px;object-fit:cover;cursor:pointer;" 
                            data-photos="{{ json_encode(array_map(fn($p) => asset('storage/'.$p), $photos)) }}"
                            onclick="openPhotoModal(this)">
                        @if(count($photos) > 1)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                +{{ count($photos) - 1 }}
                            </span>
                        @endif
                    </div>
                @endif
            @endif
            <span class="badge bg-success-subtle text-success">Masuk</span>
            @php $isAdmin = auth()->user()->hasRole('Admin') || auth()->user()->can('manage system'); @endphp
            @if($isAdmin || ($log->security_in_id === auth()->id() && $log->created_at->diffInHours(now()) < 24))
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openEditModal({{ $log->id }}, '{{ addslashes($log->plate_number) }}', '{{ addslashes($log->destination_room) }}', '{{ addslashes($log->purpose) }}', '{{ addslashes($log->notes) }}')">
                    <i class="ri-edit-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteVehicle({{ $log->id }}, '{{ addslashes($log->plate_number) }}')" title="Hapus data">
                    <i class="ri-delete-bin-line"></i>
                </button>
            @endif
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="openExitModal({{ $log->id }}, '{{ addslashes($log->plate_number) }}')" title="Kendaraan Keluar">
                <i class="ri-logout-circle-line"></i>
            </button>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-4">
        <i class="ri-car-line fs-1 d-block mb-2"></i>
        Tidak ada kendaraan di dalam
    </div>
    @endforelse
</div>
