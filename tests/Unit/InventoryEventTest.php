<?php

// // Event & Audit Logging

// // Event
// class InventoryTransactionRecorded
// {
//     public $transaction;
//     public function __construct($transaction) { $this->transaction = $transaction; }
// }

// // Listener
// class LogInventoryAudit
// {
//     public function handle(InventoryTransactionRecorded $event)
//     {
//         InventoryAudit::create([
//             'user_id' => auth()->id(),
//             'action' => 'stock_movement',
//             'auditable_type' => get_class($event->transaction),
//             'auditable_id' => $event->transaction->id,
//             'new_values' => $event->transaction->toArray(),
//             'ip_address' => request()->ip(),
//             'user_agent' => request()->userAgent(),
//         ]);
//     }
// }

// ?>

// <?php

// // Register in EventServiceProvider

// protected $listen = [
//     InventoryTransactionRecorded::class => [LogInventoryAudit::class],
// ];

?>