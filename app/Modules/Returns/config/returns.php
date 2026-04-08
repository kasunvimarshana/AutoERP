<?php

return [
    'statuses' => ['draft', 'submitted', 'approved', 'processing', 'completed', 'rejected', 'cancelled'],
    'types'    => ['purchase_return', 'sale_return'],
    'reasons'  => ['defective', 'wrong_item', 'damaged', 'overdelivery', 'quality_issue', 'other'],
    'quality_check_results' => ['passed', 'failed', 'pending', 'quarantine'],
    'restock_actions'       => ['restock', 'scrap', 'quarantine', 'return_to_supplier'],
];
