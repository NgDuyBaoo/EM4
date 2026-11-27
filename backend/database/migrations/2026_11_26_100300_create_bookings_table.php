<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mỗi dòng là 1 slot 10 phút của 1 phòng. Một lần đặt nhiều slot dùng chung booking_code.
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 12)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('booking_date');
            $table->time('slot_time');
            $table->string('customer_name', 80);
            $table->string('customer_phone', 20)->index();
            $table->enum('source', ['online', 'walkin'])->default('online');
            $table->enum('status', ['booked', 'checked_in', 'completed', 'cancelled'])->default('booked');
            $table->unsignedInteger('price')->default(0);
            $table->string('note')->default('');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->default('');
            $table->timestamps();

            // 1 khi booking còn hiệu lực, NULL khi đã hủy. Chỉ số UNIQUE bỏ qua NULL nên database
            // tự chặn đặt trùng slot (BR-06) nhưng vẫn cho đặt lại slot đã hủy.
            $table->tinyInteger('active_slot')->nullable()
                ->storedAs("CASE WHEN status = 'cancelled' THEN NULL ELSE 1 END");
            $table->unique(['room_id', 'booking_date', 'slot_time', 'active_slot'], 'bookings_room_slot_unique');

            $table->index(['branch_id', 'booking_date']);
            $table->index(['booking_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
