{{--
    Eunoia override of upstream support::rtl.styles (loaded via View::prependNamespace, no upstream edit).
    Filament 5 lays out RTL by itself from <html dir="rtl">, so upstream's extra rules double-flipped it
    (buttons drew the icon on the wrong side, F15). E1-01 removed the Google "Cairo" font (F14);
    E1-06 keeps only what Filament does not do: Latin-only inputs stay LTR (design system §4.4).
--}}
@if (in_array(app()->getLocale(), $rtlLocales))
    <style>
        [dir="rtl"] input[type="email"],
        [dir="rtl"] input[type="url"],
        [dir="rtl"] input[type="tel"],
        [dir="rtl"] input[type="number"] {
            direction: ltr;
            text-align: end;
        }
    </style>
@endif
