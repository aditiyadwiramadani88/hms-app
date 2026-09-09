@php
    $badgeMap = [
        'draft' => ['label' => 'Draft', 'class' => 'bg-secondary'],
        'submitted' => ['label' => 'Menunggu Konfirmasi', 'class' => 'bg-warning text-dark'],
        'confirmed' => ['label' => 'Terkonfirmasi', 'class' => 'bg-success'],
        'disputed' => ['label' => 'Dispute', 'class' => 'bg-danger'],
    ];
    $badge = $badgeMap[$status] ?? ['label' => $status, 'class' => 'bg-secondary'];
@endphp
<span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
