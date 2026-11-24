<?php

return [
    'slot_interval_minutes' => 30,
    'buffer_minutes' => (int) env('BOOKING_BUFFER_MINUTES', 0),
    'max_days_ahead' => 30,
];
