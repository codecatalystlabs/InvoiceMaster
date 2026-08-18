@php
    $logo = $company->logoDataUri();
@endphp
<div class="header">
    <div class="company-logo">
        @if($logo)
            <img src="{{ $logo }}" alt="{{ $company->name }}">
        @endif
        <div class="company-name">{{ $company->name }}</div>
        <div class="company-info">{!! nl2br(e($company->address ?: $company->addressText())) !!}</div>
        <div class="company-info">{{ $company->email }} | {{ $company->phone }}</div>
    </div>
</div>
