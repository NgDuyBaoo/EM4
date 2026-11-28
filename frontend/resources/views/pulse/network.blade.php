@php use App\Livewire\Pulse\Network; @endphp
<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Mạng"
        x-bind:title="`Time: {{ number_format($time, 0) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="lưu lượng vào/ra, {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.arrows-left-right />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($servers->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Máy chủ</x-pulse::th>
                        <x-pulse::th class="text-right">Nhận (hiện tại)</x-pulse::th>
                        <x-pulse::th class="text-right">Gửi (hiện tại)</x-pulse::th>
                        <x-pulse::th class="text-right">Nhận TB / cao nhất</x-pulse::th>
                        <x-pulse::th class="text-right">Gửi TB / cao nhất</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($servers as $s)
                        <tr wire:key="{{ $s->name }}-net">
                            <x-pulse::td>{{ $s->name }}</x-pulse::td>
                            <x-pulse::td numeric class="font-bold">{{ Network::human($s->rx) }}</x-pulse::td>
                            <x-pulse::td numeric class="font-bold">{{ Network::human($s->tx) }}</x-pulse::td>
                            <x-pulse::td numeric>{{ Network::human($s->rx_avg) }} / {{ Network::human($s->rx_max) }}</x-pulse::td>
                            <x-pulse::td numeric>{{ Network::human($s->tx_avg) }} / {{ Network::human($s->tx_max) }}</x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
