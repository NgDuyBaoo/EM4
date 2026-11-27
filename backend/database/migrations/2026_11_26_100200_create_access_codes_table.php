<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mã truy cập 6 số, mỗi ngày (giờ Việt Nam) một mã, dùng chung cho cả 2 chi nhánh.
        Schema::create('access_codes', function (Blueprint $table) {
            $table->date('code_date')->primary();
            $table->char('code', 6);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_codes');
    }
};
