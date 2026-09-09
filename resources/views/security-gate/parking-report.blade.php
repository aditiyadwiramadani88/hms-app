@extends('layouts.master')
@section('title') Laporan Parkir @endsection
@section('css')
<style>
    .table-parkir { font-size: 0.875rem; }
    .table-parkir th { background: #f8f9fa; text-align: center; vertical-align: middle; white-space: nowrap; }
    .table-parkir td { vertical-align: middle; }
    .table-parkir .total-row { font-weight: 700; background: #e9ecef; }
    .parkir-no { text-align: center; width: 40px; }
    .parkir-room { text-align: center; font-weight: 600; width: 80px; }
    .parkir-guest { font-weight: 500; }
    .parkir-plat { font-family: monospace; font-size: 0.8125rem; }
    .photo-icon { cursor: pointer; color: #6c757d; }
    .photo-icon:hover { color: #0d6efd; }
</style>
@endsection
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Security Gate @endslot
    @slot('title') Laporan Parkir @endslot
@endcomponent

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="ri-filter-line me-1"></i> Tampilkan</button>
                <a href="{{ route('security-gate.report.parking.pdf', ['date' => $date->format('Y-m-d')]) }}" class="btn btn-danger" target="_blank">
                    <i class="ri-file-pdf-line me-1"></i> PDF
                </a>
                <a href="{{ route('security-gate.report.parking.excel', ['date' => $date->format('Y-m-d')]) }}" class="btn btn-success">
                    <i class="ri-file-excel-line me-1"></i> Excel
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Header --}}
<div class="text-center mb-3">
    <h5 class="fw-bold mb-0">LAPORAN PARKIR HARIAN</h5>
    <small class="text-muted">{{ $date->translatedFormat('l, d F Y') }}</small>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-parkir mb-0">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>KAMAR</th>
                        <th>NAMA TAMU</th>
                        <th>MOBIL <small>HARIAN</small></th>
                        <th>MOBIL <small>KOST</small></th>
                        <th>MOTOR <small>HARIAN</small></th>
                        <th>MOTOR <small>KOST</small></th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 0; @endphp
                    @forelse($sorted as $room => $data)
                    @php
                        $rowCount = max(1,
                            count($data['mobil_harian']),
                            count($data['mobil_kost']),
                            count($data['motor_harian']),
                            count($data['motor_kost'])
                        );
                    @endphp
                    @for($i = 0; $i < $rowCount; $i++)
                    <tr>
                        @if($i === 0)
                        <td class="parkir-no" rowspan="{{ $rowCount }}">{{ ++$no }}</td>
                        <td class="parkir-room" rowspan="{{ $rowCount }}">{{ $room }}</td>
                        <td class="parkir-guest" rowspan="{{ $rowCount }}">{{ $data['guest_name'] }}</td>
                        @endif
                        <td class="parkir-plat">
                            @if(isset($data['mobil_harian'][$i]))
                                {{ $data['mobil_harian'][$i]->plate_number }}
                                @if($data['mobil_harian'][$i]->photo_in)
                                <i class="ri-camera-line photo-icon text-success" title="Foto masuk" onclick="showParkirPhoto('{{ asset('storage/'.( is_array($data['mobil_harian'][$i]->photo_in) ? $data['mobil_harian'][$i]->photo_in[0] : $data['mobil_harian'][$i]->photo_in )) }}')"></i>
                                @endif
                                @if($data['mobil_harian'][$i]->photo_out)
                                <i class="ri-camera-line photo-icon text-danger" title="Foto keluar" onclick="showParkirPhoto('{{ asset('storage/'.( is_array($data['mobil_harian'][$i]->photo_out) ? $data['mobil_harian'][$i]->photo_out[0] : $data['mobil_harian'][$i]->photo_out )) }}')"></i>
                                @endif
                            @endif
                        </td>
                        <td class="parkir-plat">
                            @if(isset($data['mobil_kost'][$i]))
                                {{ $data['mobil_kost'][$i]->plate_number }}
                                @if($data['mobil_kost'][$i]->photo_in)
                                <i class="ri-camera-line photo-icon text-success" title="Foto masuk" onclick="showParkirPhoto('{{ asset('storage/'.( is_array($data['mobil_kost'][$i]->photo_in) ? $data['mobil_kost'][$i]->photo_in[0] : $data['mobil_kost'][$i]->photo_in )) }}')"></i>
                                @endif
                                @if($data['mobil_kost'][$i]->photo_out)
                                <i class="ri-camera-line photo-icon text-danger" title="Foto keluar" onclick="showParkirPhoto('{{ asset('storage/'.( is_array($data['mobil_kost'][$i]->photo_out) ? $data['mobil_kost'][$i]->photo_out[0] : $data['mobil_kost'][$i]->photo_out )) }}')"></i>
                                @endif
                            @endif
                        </td>
                        <td class="parkir-plat">
                            @if(isset($data['motor_harian'][$i]))
                                {{ $data['motor_harian'][$i]->plate_number }}
                                @if($data['motor_harian'][$i]->photo_in)
                                <i class="ri-camera-line photo-icon text-success" title="Foto masuk" onclick="showParkirPhoto('{{ asset('storage/'.( is_array($data['motor_harian'][$i]->photo_in) ? $data['motor_harian'][$i]->photo_in[0] : $data['motor_harian'][$i]->photo_in )) }}')"></i>
                                @endif
                                @if($data['motor_harian'][$i]->photo_out)
                                <i class="ri-camera-line photo-icon text-danger" title="Foto keluar" onclick="showParkirPhoto('{{ asset('storage/'.( is_array($data['motor_harian'][$i]->photo_out) ? $data['motor_harian'][$i]->photo_out[0] : $data['motor_harian'][$i]->photo_out )) }}')"></i>
                                @endif
                            @endif
                        </td>
                        <td class="parkir-plat">
                            @if(isset($data['motor_kost'][$i]))
                                {{ $data['motor_kost'][$i]->plate_number }}
                                @if($data['motor_kost'][$i]->photo_in)
                                <i class="ri-camera-line photo-icon text-success" title="Foto masuk" onclick="showParkirPhoto('{{ asset('storage/'.( is_array($data['motor_kost'][$i]->photo_in) ? $data['motor_kost'][$i]->photo_in[0] : $data['motor_kost'][$i]->photo_in )) }}')"></i>
                                @endif
                                @if($data['motor_kost'][$i]->photo_out)
                                <i class="ri-camera-line photo-icon text-danger" title="Foto keluar" onclick="showParkirPhoto('{{ asset('storage/'.( is_array($data['motor_kost'][$i]->photo_out) ? $data['motor_kost'][$i]->photo_out[0] : $data['motor_kost'][$i]->photo_out )) }}')"></i>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endfor
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data parkir untuk tanggal ini</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-end">TOTAL</td>
                        <td class="text-center">{{ $totals['mobil_harian'] }}</td>
                        <td class="text-center">{{ $totals['mobil_kost'] }}</td>
                        <td class="text-center">{{ $totals['motor_harian'] }}</td>
                        <td class="text-center">{{ $totals['motor_kost'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Photo Modal --}}
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-1">
                <img loading="lazy" id="parkirPhoto" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
function showParkirPhoto(src) {
    document.getElementById('parkirPhoto').src = src;
    new bootstrap.Modal(document.getElementById('photoModal')).show();
}
</script>
@endsection
