@props(['weather'])
@if ($weather)
    <div {{ $attributes->merge(['class' => 'card flex items-center gap-4']) }}>
        <span class="text-4xl" aria-hidden="true">{{ $weather['icon'] }}</span>
        <div>
            <div class="font-semibold">Huế hôm nay: {{ $weather['label'] }}, {{ $weather['temperature'] }}°C</div>
            <div class="text-sm text-ngoi-muted">{{ $weather['min'] }}–{{ $weather['max'] }}°C · Khả năng mưa {{ $weather['rain_chance'] }}%
                @if ($weather['rain_chance'] >= 60) · Nhớ mang ô và đến sớm vài phút nhé! @endif
            </div>
            <div class="text-[11px] text-ngoi-muted">Nguồn: Open-Meteo</div>
        </div>
    </div>
@endif
