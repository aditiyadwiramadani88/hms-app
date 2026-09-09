@extends('layouts.master')
@section('title', 'Edit Asset')
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asset Management @endslot
        @slot('title') Edit Asset @endslot
    @endcomponent

    <form action="{{ route('assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="name">Nama Asset <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $asset->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="asset_category_id">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('asset_category_id') is-invalid @enderror" id="asset_category_id" name="asset_category_id" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('asset_category_id', $asset->asset_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('asset_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="serial_number">Serial Number</label>
                                <input type="text" class="form-control @error('serial_number') is-invalid @enderror" id="serial_number" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}">
                                @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="purchase_year">Tahun Beli <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('purchase_year') is-invalid @enderror" id="purchase_year" name="purchase_year" value="{{ old('purchase_year', $asset->purchase_year) }}" required min="1900" max="{{ date('Y') }}">
                                @error('purchase_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="purchase_price">Harga Beli</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $asset->purchase_price) }}" min="0" step="1000">
                                </div>
                                @error('purchase_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="supplier">Supplier / Toko</label>
                                <input type="text" class="form-control @error('supplier') is-invalid @enderror" id="supplier" name="supplier" value="{{ old('supplier', $asset->supplier) }}">
                                @error('supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="quantity">Jumlah (Qty) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" value="{{ old('quantity', $asset->quantity) }}" required min="1">
                                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="fs-14 mb-3 text-muted">Penempatan Asset</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe Lokasi <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="location_type" id="loc_room" value="room" {{ old('location_type', $asset->location_type) == 'room' ? 'checked' : '' }} onchange="toggleLocation()">
                                        <label class="form-check-label" for="loc_room">Kamar (Room)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="location_type" id="loc_area" value="area" {{ old('location_type', $asset->location_type) == 'area' ? 'checked' : '' }} onchange="toggleLocation()">
                                        <label class="form-check-label" for="loc_area">Area Umum</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="room_select_div">
                                <label class="form-label" for="room_id">Pilih Kamar <span class="text-danger">*</span></label>
                                <select class="form-select @error('room_id') is-invalid @enderror" id="room_id" name="room_id">
                                    <option value="">Pilih Kamar</option>
                                    @foreach($rooms as $r)
                                        <option value="{{ $r->id }}" {{ old('room_id', $asset->room_id) == $r->id ? 'selected' : '' }}>Room {{ $r->room_number }}</option>
                                    @endforeach
                                </select>
                                @error('room_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3" id="area_input_div" style="display: none;">
                                <label class="form-label" for="area_name">Nama Area <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('area_name') is-invalid @enderror" id="area_name" name="area_name" value="{{ old('area_name', $asset->area_name) }}" placeholder="Contoh: Lobby, Dapur">
                                @error('area_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="fs-14 mb-3 text-muted">Person In Charge (PIC)</h5>

                        @php
                            $currentPicType = old('pic_type', $asset->pic_role ? 'role' : 'user');
                        @endphp

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe PIC <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pic_type" id="pic_user" value="user" {{ $currentPicType == 'user' ? 'checked' : '' }} onchange="togglePic()">
                                        <label class="form-check-label" for="pic_user">Pilih User Spesifik</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pic_type" id="pic_role" value="role" {{ $currentPicType == 'role' ? 'checked' : '' }} onchange="togglePic()">
                                        <label class="form-check-label" for="pic_role">Pilih Berdasarkan Role</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="pic_user_div">
                                <label class="form-label" for="pic_user_id">Pilih User <span class="text-danger">*</span></label>
                                <select class="form-select @error('pic_user_id') is-invalid @enderror" id="pic_user_id" name="pic_user_id">
                                    <option value="">Pilih User</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ old('pic_user_id', $asset->pic_user_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                                    @endforeach
                                </select>
                                @error('pic_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3" id="pic_role_div" style="display: none;">
                                <label class="form-label" for="pic_role_input">Pilih Role <span class="text-danger">*</span></label>
                                <select class="form-select @error('pic_role') is-invalid @enderror" id="pic_role_input" name="pic_role">
                                    <option value="">Pilih Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('pic_role', $asset->pic_role) == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                @error('pic_role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Foto & Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4 text-center">
                            @if($asset->photo)
                                <img loading="lazy" src="{{ Storage::url($asset->photo) }}" alt="" class="img-thumbnail w-100 mb-3" style="max-height: 250px; object-fit: contain;">
                            @else
                                <div class="p-5 bg-light mb-3 rounded text-center">
                                    <i class="ri-image-line text-muted display-4"></i>
                                    <p class="text-muted mb-0">Belum ada foto</p>
                                </div>
                            @endif
                            <div class="text-start">
                                <label for="photo" class="form-label">Ubah Foto Asset</label>
                                <input class="form-control" type="file" id="photo" name="photo" accept="image/png, image/jpeg, image/jpg">
                                <small class="text-muted">Maksimal ukuran file 2MB.</small>
                                @error('photo') <div class="text-danger mt-1 fs-12">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status Saat Ini</label>
                            @php
                                $statusColor = 'success';
                                if($asset->status == 'Rusak Ringan') $statusColor = 'warning';
                                elseif($asset->status == 'Rusak Berat') $statusColor = 'danger';
                                elseif($asset->status == 'Maintenance') $statusColor = 'info';
                                elseif($asset->status == 'Menunggu Diganti') $statusColor = 'secondary';
                                elseif($asset->status == 'Sudah Dibuang') $statusColor = 'dark';
                            @endphp
                            <div>
                                <span class="badge bg-{{ $statusColor }} fs-13 px-3 py-2 w-100 text-uppercase">{{ $asset->status }}</span>
                            </div>
                            <small class="text-muted mt-2 d-block">Status hanya dapat diubah melalui menu update status di halaman detail atau daftar asset.</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                            <a href="{{ route('assets.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
    function toggleLocation() {
        const type = document.querySelector('input[name="location_type"]:checked').value;
        const roomDiv = document.getElementById('room_select_div');
        const areaDiv = document.getElementById('area_input_div');
        const roomId = document.getElementById('room_id');
        const areaName = document.getElementById('area_name');

        if (type === 'room') {
            roomDiv.style.display = 'block';
            areaDiv.style.display = 'none';
            roomId.required = true;
            areaName.required = false;
        } else {
            roomDiv.style.display = 'none';
            areaDiv.style.display = 'block';
            roomId.required = false;
            areaName.required = true;
        }
    }

    function togglePic() {
        const type = document.querySelector('input[name="pic_type"]:checked').value;
        const userDiv = document.getElementById('pic_user_div');
        const roleDiv = document.getElementById('pic_role_div');
        const userId = document.getElementById('pic_user_id');
        const roleName = document.getElementById('pic_role_input');

        if (type === 'user') {
            userDiv.style.display = 'block';
            roleDiv.style.display = 'none';
            userId.required = true;
            roleName.required = false;
        } else {
            userDiv.style.display = 'none';
            roleDiv.style.display = 'block';
            userId.required = false;
            roleName.required = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleLocation();
        togglePic();
    });
</script>
@endsection