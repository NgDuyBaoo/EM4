<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Lời nhắn
 */
class MessageController extends Controller
{
    /** Danh sách lời nhắn (nhân viên). */
    public function index(Request $request): AnonymousResourceCollection
    {
        return MessageResource::collection(
            Message::with('branch')->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))->latest()->paginate(20)
        );
    }

    /**
     * Khách gửi lời nhắn.
     *
     * @unauthenticated
     */
    public function store(MessageRequest $request): JsonResponse
    {
        return (new MessageResource(Message::create($request->validated())))->response()->setStatusCode(201);
    }

    /** Chi tiết lời nhắn (nhân viên). */
    public function show(Message $message): MessageResource
    {
        return new MessageResource($message->load('branch'));
    }

    /** Đánh dấu đã đọc / chưa đọc (nhân viên). */
    public function update(Request $request, Message $message): MessageResource
    {
        $request->validate(['read' => ['required', 'boolean']]);
        $message->update(['read_at' => $request->boolean('read') ? now() : null]);

        return new MessageResource($message);
    }

    /** Xóa lời nhắn (nhân viên). */
    public function destroy(Message $message): JsonResponse
    {
        $message->delete();

        return response()->json(null, 204);
    }
}
