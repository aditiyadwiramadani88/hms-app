@extends('layouts.master')
@section('title')
    Reports & Analytics
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Reports @endslot
        @slot('title') Reports & Analytics @endslot
    @endcomponent

    {{-- Quick Access: Laporan Kost --}}
    <div class="card border-primary">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1"><i class="ri-home-4-line me-2 text-primary"></i>Laporan Kost Bulanan</h5>
                <p class="text-muted mb-0">Laporan penghuni kost per bulan dengan detail pembayaran per rekening.</p>
            </div>
            <a href="{{ route('reports.kost') }}" class="btn btn-primary">
                <i class="ri-arrow-right-line me-1"></i> Buka Laporan Kost
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-bar-chart-fill me-2 text-primary"></i>Report Configuration</h5></div>
        <div class="card-body">
            <form id="reportForm">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-4">
                        <label class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="occupancy">Occupancy Report</option>
                            <option value="revenue">Revenue Report</option>
                            <option value="transactions">Comprehensive Transactions Report</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-4">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="start_date" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-xl-3 col-md-4">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="end_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-xl-3 col-md-12">
                        <button type="submit" data-submit-protect="true" class="btn btn-primary w-100" id="btnGenerate">
                            <i class="ri-file-chart-line me-1 align-bottom"></i> Generate Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="reportLoading" style="display: none;" class="text-center my-5">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
        <h5 class="mt-3">Generating report, please wait...</h5>
    </div>

    <div id="reportResultsContainer">
        {{-- AJAX results will be injected here --}}
        <div class="card">
            <div class="card-body text-center py-5">
                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:100px;height:100px"></lord-icon>
                <h5 class="mt-4">Ready to Generate</h5>
                <p class="text-muted">Select a report type and date range above to view results.</p>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleTimeFilters() {
            const type = document.getElementById('report_type').val;
            if (type === 'room_activity') {
                $('.time-filter').show();
                $('.type-filter').show();
            } else {
                $('.time-filter').hide();
                $('.type-filter').hide();
            }
        }

        document.getElementById('report_type').addEventListener('change', function(e) {
            if (e.target.value === 'room_activity') {
                $('.time-filter').show();
                $('.type-filter').show();
            } else {
                $('.time-filter').hide();
                $('.type-filter').hide();
            }
        });

        $(document).ready(function() {
            $('#reportForm').on('submit', function(e) {
                e.preventDefault();
                
                const type = $('#report_type').val();
                const startDate = $('#date_from').val();
                const endDate = $('#date_to').val();
                const startTime = $('#time_from').val();
                const endTime = $('#time_to').val();
                const activityType = $('#activity_type').val();
                
                if (!type) return;

                // Show loading
                $('#reportResultsContainer').hide();
                $('#reportLoading').show();

                let url = '';
                if (type === 'occupancy') url = '{{ route("reports.occupancy") }}';
                else if (type === 'revenue') url = '{{ route("reports.revenue") }}';
                else if (type === 'transactions') url = '{{ route("reports.transactions") }}';
                else if (type === 'room_activity') url = '{{ route("reports.room_activity") }}';

                let exportUrl = `/reports/export/${type === 'transactions' ? 'transactions' : (type === 'room_activity' ? 'room-activity' : type + '/excel')}?start_date=${startDate}&end_date=${endDate}`;
                if (type === 'room_activity') {
                    exportUrl += `&start_time=${startTime}&end_time=${endTime}&activity_type=${activityType}`;
                }

                $.ajax({
                    url: url,
                    method: 'GET',
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                        start_time: startTime,
                        end_time: endTime,
                        activity_type: activityType,
                        ajax: 1
                    },
                    success: function(response) {
                        $('#reportLoading').hide();
                        $('#reportResultsContainer').html(`
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <h5 class="card-title mb-0 flex-grow-1">Report Results (${startDate} to ${endDate})</h5>
                                    <div class="flex-shrink-0">
                                        <a href="${exportUrl}" class="btn btn-success btn-sm">
                                            <i class="ri-file-excel-2-line align-bottom me-1"></i> Export Excel
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    ${response}
                                </div>
                            </div>
                        `).show();
                    },
                    error: function(xhr) {
                        $('#reportLoading').hide();
                        $('#reportResultsContainer').html(`
                            <div class="alert alert-danger">Failed to generate report. Please try again.</div>
                        `).show();
                    }
                });
            });
        });
    </script>
@endsection
