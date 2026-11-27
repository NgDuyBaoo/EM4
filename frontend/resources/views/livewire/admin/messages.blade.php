<div>
    <h1 class="font-serif text-3xl">Góp ý & lời nhắn</h1>
    <p class="mb-4 text-ngoi-muted">Lời nhắn khách gửi từ trang Liên hệ.</p>
    @forelse ($messages as $m)
        <div class="card mb-3 {{ $m->read_at ? '' : 'border-l-4 border-l-ngoi-brand' }}" wire:key="m-{{ $m->id }}">
            <div class="flex flex-wrap justify-between gap-2">
                <b>{{ $m->name ?: 'Khách' }} · {{ $m->contact }}</b>
                <span class="text-sm text-ngoi-muted">{{ $m->created_at->format('d/m H:i') }} · {{ $m->branch?->name ?? 'Góp ý chung' }} · {{ $m->subject }}</span>
            </div>
            <p class="my-2 whitespace-pre-wrap">{{ $m->body }}</p>
            <button class="btn-ghost btn-sm" wire:click="toggle({{ $m->id }})">{{ $m->read_at ? 'Đánh dấu chưa đọc' : 'Đánh dấu đã đọc' }}</button>
        </div>
    @empty
        <div class="card">Chưa có lời nhắn nào.</div>
    @endforelse
    {{ $messages->links() }}
</div>
