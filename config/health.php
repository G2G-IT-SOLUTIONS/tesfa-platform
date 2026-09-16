<?php

return [
    'checks' => [
        Spatie\Health\Checks\Checks\DatabaseCheck::class,
        Spatie\Health\Checks\Checks\UsedDiskSpaceCheck::class,
        Spatie\Health\Checks\Checks\QueueCheck::class,
    ],
];