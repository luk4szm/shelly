<?php

namespace App\Enum;

enum SeasonMode: string
{
    case Winter       = 'winter';       // Zima
    case Summer       = 'summer';       // Lato
    case Transitional = 'transitional'; // Okres przejściowy
}
