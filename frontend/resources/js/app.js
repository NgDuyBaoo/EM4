// Livewire 4 tự nạp Alpine.js; thêm JS riêng của giao diện ở đây nếu cần.
document.addEventListener('livewire:navigated', () => window.scrollTo({ top: 0 }));
