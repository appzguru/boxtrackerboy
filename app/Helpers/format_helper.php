<?php

if (! function_exists('box_nr')) {
    function box_nr(int $nummer): string
    {
        return str_pad((string) $nummer, 3, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('nl_datetime')) {
    function nl_datetime(?string $datetime): string
    {
        if (! $datetime) {
            return '';
        }
        $ts  = strtotime($datetime);
        $dag = date('Y-m-d', $ts) === date('Y-m-d') ? 'Vandaag' : ucfirst(strftime_nl($ts));

        return $dag . ', ' . date('H:i', $ts);
    }
}

if (! function_exists('nl_date')) {
    /** "di 14 okt 2026" (of '' zonder datum). */
    function nl_date(?string $date): string
    {
        if (! $date) {
            return '';
        }
        $ts = strtotime($date);

        return strftime_nl($ts) . ' ' . date('Y', $ts);
    }
}

if (! function_exists('strftime_nl')) {
    function strftime_nl(int $ts): string
    {
        $dagen    = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'];
        $maanden  = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

        return $dagen[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' ' . $maanden[(int) date('n', $ts) - 1];
    }
}

if (! function_exists('first_line')) {
    function first_line(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        $lines = preg_split('/\r\n|\r|\n/', $text);

        return $lines[0];
    }
}

/** Statuslabel + kleuren voor pill-badges, zie boxtracker-ontwerp_v2 (kartonlook). */
if (! function_exists('status_pill')) {
    function status_pill(string $status): array
    {
        return match ($status) {
            'leeg'      => ['label' => 'Nieuwe doos', 'bg' => '#1A140E', 'fg' => '#FFFDF8'],
            'ingepakt'  => ['label' => 'Ingepakt', 'bg' => '#EBD5B3', 'fg' => '#6E4424'],
            'opgeslagen' => ['label' => 'In opslag', 'bg' => '#F0DFC4', 'fg' => '#6E4424'],
            'geopend'   => ['label' => 'Geopend', 'bg' => '#F0DFC4', 'fg' => '#6E4424'],
            'uitgepakt' => ['label' => 'Uitgepakt', 'bg' => '#DFF3E8', 'fg' => '#1A7F4B'],
            default     => ['label' => $status, 'bg' => '#EFE3CF', 'fg' => '#6B5C4A'],
        };
    }
}
