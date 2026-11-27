<?php

namespace App\Enums;

enum BookingSource: string
{
    case Online = 'online';
    case Walkin = 'walkin';

    public function label(): string
    {
        return $this === self::Online ? 'Online' : 'Tại quầy';
    }
}
