@extends('layouts.master')
@section('title')
    Add New Guest
@endsection
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Guests
        @endslot
        @slot('title')
            Add New Guest
        @endslot
    @endcomponent

    <form action="{{ route('guests.store') }}" method="POST" data-ajax="true" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-user-fill me-2 text-primary"></i>Guest Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}"
                                           placeholder="Enter full name" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- Guest Category disembunyikan: redundant dgn Customer Type & path pricing-nya
                                 tidak aktif (tidak ada RoomRate per guest_category). Kolom DB dipertahankan. --}}
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email') }}"
                                           placeholder="Enter email address (opsional)">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" value="{{ old('phone') }}"
                                           placeholder="Enter phone number">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">ID / Passport Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('id_number') is-invalid @enderror"
                                           id="id_number" name="id_number" value="{{ old('id_number') }}"
                                           placeholder="Enter ID or passport number" required>
                                    @error('id_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_card_photo" class="form-label">Photo ID (KTP/Passport) <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control @error('id_card_photo') is-invalid @enderror"
                                           id="id_card_photo" name="id_card_photo" accept="image/*" required>
                                    @error('id_card_photo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reference_source" class="form-label">Reference Source</label>
                                    <input type="text" class="form-control @error('reference_source') is-invalid @enderror"
                                           id="reference_source" name="reference_source" value="{{ old('reference_source') }}"
                                           placeholder="e.g. Walk-in, OTA, Friend">
                                    @error('reference_source')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nationality" class="form-label">Nationality</label>
                                    <input type="text" class="form-control @error('nationality') is-invalid @enderror"
                                           id="nationality" name="nationality" value="{{ old('nationality') }}"
                                           placeholder="Enter nationality">
                                    @error('nationality')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror"
                                           id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                                    @error('date_of_birth')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address" name="address" rows="2"
                                      placeholder="Enter address">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      id="notes" name="notes" rows="3"
                                      placeholder="Any special notes about this guest...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Emergency Contacts --}}
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1"><i class="ri-phone-fill me-2 text-warning"></i>Kontak Darurat</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" id="addEmergencyContact">
                            <i class="ri-add-line me-1"></i> Tambah Kontak
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Kontak yang bisa dihubungi dalam keadaan darurat (opsional).</p>
                        <div id="emergency_contacts_container">
                            <div class="text-center text-muted py-2" id="no_contacts_msg">
                                <small>Belum ada kontak darurat.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-success">
                                <i class="ri-save-line me-1 align-bottom"></i> Save Guest
                            </button>
                            <a href="{{ route('guests.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Guest Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            <i class="ri-information-fill me-1"></i>
                            Fill in the guest details to add them to the system.
                            Name and phone are required fields.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#guest_category_id').select2({
                placeholder: "Select Category",
                allowClear: true
            });
        });

        function compressImage(file, maxW, quality, callback) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let w = img.width, h = img.height;
                    if (w > maxW) { h = h * maxW / w; w = maxW; }
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    canvas.toBlob(function(blob) {
                        const f = new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() });
                        callback(f);
                    }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        document.getElementById('id_card_photo').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            if (file.size < 500 * 1024) return; // skip if already under 500KB
            const label = this.parentNode.querySelector('label') || this.previousElementSibling;
            const origText = label ? label.textContent : '';
            if (label) label.textContent = 'Compressing...';
            compressImage(file, 1200, 0.65, function(compressed) {
                const dt = new DataTransfer();
                dt.items.add(compressed);
                document.getElementById('id_card_photo').files = dt.files;
                if (label) label.textContent = origText + ' (compressed: ' + Math.round(compressed.size/1024) + 'KB)';
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('emergency_contacts_container');

            document.getElementById('addEmergencyContact').addEventListener('click', function() {
                addEmergencyContactRow();
            });

            container.addEventListener('click', function(e) {
                if (e.target.closest('.remove-contact')) {
                    e.target.closest('.emergency-contact-row').remove();
                    updateContactMsg();
                }
            });

            function addEmergencyContactRow() {
                const rows = container.querySelectorAll('.emergency-contact-row');
                const idx = rows.length;
                const html = `<div class="row g-2 mb-2 emergency-contact-row">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="emergency_contacts[${idx}][contact_name]" placeholder="Nama" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="emergency_contacts[${idx}][phone_number]" placeholder="No. Telpon" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="emergency_contacts[${idx}][relationship]" placeholder="Hubungan (opsional)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-soft-danger w-100 remove-contact">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
                updateContactMsg();
            }

            function updateContactMsg() {
                const rows = container.querySelectorAll('.emergency-contact-row');
                document.getElementById('no_contacts_msg').style.display = rows.length > 0 ? 'none' : '';
            }
        });
    </script>
@endsection
