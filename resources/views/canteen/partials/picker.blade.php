@php
    $selected = $selected ?? collect();
    $copy = [
        'sauce' => 'Pay for the source you ordered. The foods below are included in that price.',
        'food' => 'Tick what was served with the source. These do not add to the bill.',
        'drink' => 'Drinks are charged separately.',
        'extra' => 'Extras are charged separately.',
    ];
@endphp
@foreach(\App\Models\CanteenItem::types() as $type => $label)
    @if(($items[$type] ?? collect())->isEmpty())
        @continue
    @endif
    <h6 class="mt-3 mb-1">{{ $label }}</h6>
    <p class="text-muted small">{{ $copy[$type] ?? '' }}</p>
    <div class="food-grid">
        @foreach($items[$type] as $item)
            @php $on = $selected->has($item->id) || (old('item_ids') && in_array($item->id, old('item_ids', []))); @endphp
            <label class="food-card">
                <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" class="food-check" @checked($on)>
                <strong>{{ $item->name }}</strong>
                <span>
                    @if($item->is_priced)
                        {{ money($item->price) }} / {{ $item->unit }}
                    @else
                        Included / {{ $item->unit }}
                    @endif
                </span>
                <input type="number" min="1" step="1" name="qty[{{ $item->id }}]" value="{{ old('qty.'.$item->id, $selected[$item->id] ?? 1) }}" class="form-control form-control-sm mt-2" onclick="event.stopPropagation()">
            </label>
        @endforeach
    </div>
@endforeach
