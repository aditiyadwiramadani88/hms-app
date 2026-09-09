@php
    $fs = $financialSummary;
@endphp
<div class="row">
    <div class="col-md-3">
        <div class="card bg-success bg-opacity-10 border-success">
            <div class="card-body text-center">
                <h6 class="card-title text-success">Pemasukan Room</h6>
                <h4 class="text-success">@money($fs['pemasukan_room'] ?? 0)</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success bg-opacity-10 border-success">
            <div class="card-body text-center">
                <h6 class="card-title text-success">Pemasukan Lain</h6>
                <h4 class="text-success">@money($fs['pemasukan_lain'] ?? 0)</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger bg-opacity-10 border-danger">
            <div class="card-body text-center">
                <h6 class="card-title text-danger">Pengeluaran</h6>
                <h4 class="text-danger">@money($fs['pengeluaran'] ?? 0)</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary bg-opacity-10 border-primary">
            <div class="card-body text-center">
                <h6 class="card-title text-primary">Sisa Kas (Net Balance)</h6>
                <h4 class="text-primary">@money($fs['net_balance'] ?? 0)</h4>
            </div>
        </div>
    </div>
</div>
