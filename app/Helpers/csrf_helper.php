<?php

if (! function_exists('csrf_field')) {
    /** Verborgen CSRF-inputveld voor een formulier. Deze CI4-versie levert geen
     *  csrf_field() mee (dat kwam pas later), dus zelf gebouwd op de Security-service. */
    function csrf_field(): string
    {
        $security = service('security');

        return '<input type="hidden" name="' . esc($security->getTokenName(), 'attr') . '" value="' . esc((string) $security->getHash(), 'attr') . '">';
    }
}
