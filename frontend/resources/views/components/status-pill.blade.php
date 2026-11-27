@props(['status'])
<span class="pill {{ $status->badge() }}">{{ $status->label() }}</span>
