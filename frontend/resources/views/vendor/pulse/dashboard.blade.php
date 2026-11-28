{{-- Dashboard /pulse của Ngói: thêm thẻ "Mạng" (recorder App\Pulse\Recorders\Network) cạnh thẻ máy chủ mặc định. --}}
<x-pulse>
    <livewire:pulse.servers cols="full" />

    <livewire:ngoi-pulse-network cols="full" />

    <livewire:pulse.usage cols="4" rows="2" />

    <livewire:pulse.queues cols="4" />

    <livewire:pulse.cache cols="4" />

    <livewire:pulse.slow-queries cols="8" />

    <livewire:pulse.exceptions cols="6" />

    <livewire:pulse.slow-requests cols="6" />

    <livewire:pulse.slow-jobs cols="6" />

    <livewire:pulse.slow-outgoing-requests cols="6" />
</x-pulse>
