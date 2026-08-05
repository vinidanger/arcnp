<?php

use App\Support\StatusLabel;

if (! function_exists('status_label')) {
    function status_label(?string $value): string
    {
        return StatusLabel::translate($value);
    }
}
