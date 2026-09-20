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

/** Statuslabel + kleuren voor pill-badges, zie boxtracker-ontwerp. */
if (! function_exists('status_pill')) {
    function status_pill(string $status): array
    {
        return match ($status) {
            'leeg'      => ['label' => 'Nieuwe doos', 'bg' => '#0F1216', 'fg' => '#FFFFFF'],
            'ingepakt'  => ['label' => 'Ingepakt', 'bg' => '#FFF1D0', 'fg' => '#7A4B00'],
            'opgeslagen' => ['label' => 'In opslag', 'bg' => '#EAEDFF', 'fg' => '#2433C7'],
            'geopend'   => ['label' => 'Geopend', 'bg' => '#EAEDFF', 'fg' => '#2433C7'],
            'uitgepakt' => ['label' => 'Uitgepakt', 'bg' => '#DDF4E7', 'fg' => '#10633B'],
            default     => ['label' => $status, 'bg' => '#EEF0F5', 'fg' => '#5B6573'],
        };
    }
}
