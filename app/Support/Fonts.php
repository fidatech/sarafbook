<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/** The font of Dari and Pashto text (see config/fonts.php). */
class Fonts
{
    /**
     * A small style block for the head of a page. The font is used for Arabic-script letters only
     * (unicode-range), so English text and numbers keep the usual font and the file is not even
     * downloaded on a page without Dari or Pashto text.
     */
    public static function style(): HtmlString
    {
        $fonts = config('fonts.list', []);
        $key = config('fonts.'.(app()->getLocale() === 'ps' ? 'pashto' : 'dari'));
        if (! $key || ! isset($fonts[$key])) {
            return new HtmlString('');
        }
        $fallback = text_dir() === 'rtl'
            ? "'Segoe UI',Tahoma,'Noto Naskh Arabic','Noto Sans Arabic','Geeza Pro',Arial,sans-serif"
            : "'Nunito',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif";

        $css = '';
        foreach ($fonts[$key]['faces'] as [$file, $format, $weight]) {
            $css .= '@font-face{font-family:"ErpFont";src:url("'.e(asset_v('fonts/'.$file)).'") format("'.$format.'");font-weight:'.$weight.';font-display:swap;'
                .'unicode-range:U+0600-06FF,U+0750-077F,U+FB50-FDFF,U+FE70-FEFF,U+200C-200F}';
        }

        return new HtmlString('<style>'.$css.'html[dir] body{font-family:"ErpFont",'.$fallback.'}</style>');
    }
}
