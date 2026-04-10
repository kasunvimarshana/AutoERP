<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('item_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('serial_id')->constrained('serials');
            $table->morphs('trackable'); // PO, SO, Return, etc.
            $table->enum('event', [
                'received', 'inspected', 'stored', 'picked', 
                'packed', 'shipped', 'delivered', 'returned', 
                'damaged', 'lost', 'scraped'
            ]);
            $table->dateTime('event_timestamp');
            $table->string('location')->nullable();
            $table->string('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['serial_id', 'event_timestamp']);
        });
    }
};