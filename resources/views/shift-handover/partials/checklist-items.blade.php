@props(['items', 'mode' => 'display', 'disabled' => false])

@if($mode === 'form')
    @foreach($items as $item)
    <div class="mb-2">
        <div class="d-flex align-items-start gap-2">
            <div class="form-check">
                <input type="hidden" name="items[{{ $item->id }}][is_checked]" value="0">
                <input class="form-check-input checklist-required" type="checkbox"
                    name="items[{{ $item->id }}][is_checked]" value="1"
                    id="item_{{ $item->id }}"
                    {{ $item->is_checked ? 'checked' : '' }}
                    {{ $disabled ? 'disabled' : '' }}
                    data-required="{{ $item->is_required ? 'true' : 'false' }}">
            </div>
            <div class="flex-grow-1">
                <label for="item_{{ $item->id }}" class="form-label mb-0">
                    {{ $item->item_name }}
                    @if($item->is_required)
                        <span class="text-danger">*</span>
                    @endif
                </label>
                @if($item->description)
                    <small class="text-muted d-block">{{ $item->description }}</small>
                @endif
                <div class="row mt-1">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="items[{{ $item->id }}][value]"
                            value="{{ $item->value }}" placeholder="Nilai" maxlength="100"
                            {{ $disabled ? 'disabled' : '' }}>
                    </div>
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-sm" name="items[{{ $item->id }}][notes]"
                            value="{{ $item->notes }}" placeholder="Catatan (max 500 karakter)" maxlength="500"
                            {{ $disabled ? 'disabled' : '' }}>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@else
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th style="width: 40px;">Status</th>
                    <th>Item</th>
                    <th style="width: 120px;">Nilai</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td class="text-center">
                        @if($item->is_checked)
                            <i class="ri-checkbox-circle-fill text-success fs-5"></i>
                        @else
                            <i class="ri-close-circle-fill text-danger fs-5"></i>
                        @endif
                    </td>
                    <td>
                        {{ $item->item_name }}
                        @if($item->is_required)
                            <span class="text-danger">*</span>
                        @endif
                    </td>
                    <td>{{ $item->value ?? '-' }}</td>
                    <td>{{ $item->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Tidak ada item checklist</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
