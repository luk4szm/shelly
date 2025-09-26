<?php

namespace App\Utils;

use App\Entity\HeatingNote;

final class HeatingNoteSerializer
{
    public static function serialize(HeatingNote $note): array
    {
        return [
            'time' => $note->getTime()->format('Y-m-d H:i:s'),
            'note' => $note->getNote(),
        ];
    }
}