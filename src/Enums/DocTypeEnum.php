<?php

namespace Hwkdo\D3RestLaravel\Enums;

use App\Traits\hasEnumTrait;

enum DocTypeEnum: string
{
    use hasEnumTrait;

    case Bestellvorgang = 'BESTV';
    case Bestellschein = 'BESTS';
    case Angebote = 'ANGEB';
    case Handwerksrolle = 'DHR';
    case Zahlungsbeleg = 'ZAHLB';
    case Lieferschein = 'LS';
    case HandwerksrolleOnline = 'HWRO';
}
