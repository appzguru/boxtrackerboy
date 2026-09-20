<?php

if (! function_exists('icon')) {
    /** Kleine inline-SVG iconen, paden overgenomen uit boxtracker-ontwerp. */
    function icon(string $name, int $size = 20): string
    {
        $paths = [
            'back'      => '<path d="M15 5l-7 7 7 7"/>',
            'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
            'move'      => '<path d="M4 8h14M14 4l4 4-4 4M20 16H6M10 12l-4 4 4 4"/>',
            'grid'      => '<rect x="4" y="4" width="7" height="7" rx="2"/><rect x="13" y="4" width="7" height="7" rx="2"/><rect x="4" y="13" width="7" height="7" rx="2"/><rect x="13" y="13" width="7" height="7" rx="2"/>',
            'labels'    => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.3"/>',
            'fragile'   => '<path d="M7 3h10l-1 7a4 4 0 0 1-8 0zM12 14v6M8.5 20h7"/>',
            'first'     => '<path d="M4 12v7h16v-7M12 14V3M8 7l4-4 4 4"/>',
            'camera'    => '<path d="M4 8h3l1.5-2h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
            'photo'     => '<rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="1.6"/><path d="M4 18l5-5 4 4 3-3 4 4"/>',
            'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
            'plus'      => '<path d="M12 5v14M5 12h14"/>',
            'mic'       => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/>',
            'edit'      => '<path d="M4 20h4L19 9l-4-4L4 16z"/>',
            'unpack'    => '<path d="M3 7.5l9-4.5 9 4.5v9l-9 4.5-9-4.5z"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>',
            'refresh'   => '<path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/>',
            'warning'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/>',
            'upload'    => '<path d="M12 16V4M7 9l5-5 5 5M4 20h16"/>',
            'newscan'   => '<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M4 12h16"/>',
            'list'      => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
            'close'     => '<path d="M6 6l12 12M18 6L6 18"/>',
            'box'       => '<path d="M3 7.5l9-4.5 9 4.5v9l-9 4.5-9-4.5z"/><path d="M3 7.5l9 4.5 9-4.5M12 12v9"/>',
        ];
        $body = $paths[$name] ?? '';

        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex:none;">' . $body . '</svg>';
    }
}
