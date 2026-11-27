<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Models\Branch;
use App\Models\Message;
use App\Services\WeatherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(WeatherService $weather): View
    {
        return view('pages.home', [
            'branches' => $this->branches(),
            'weather' => $weather->today(),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', ['branches' => $this->branches()]);
    }

    /** Form liên hệ: @csrf + kiểm tra dữ liệu bằng MessageRequest phía server. */
    public function sendMessage(MessageRequest $request): RedirectResponse
    {
        Message::create($request->validated());

        return back()->with('status', 'Ngói đã nhận lời nhắn. Cảm ơn bạn, Ngói sẽ liên hệ lại sớm!');
    }

    private function branches()
    {
        return Cache::remember('catalog:branches', 600, fn () => Branch::active()->with('activeRooms')->withCount('activeRooms')->get());
    }
}
