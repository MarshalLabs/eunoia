{{--
    Eunoia override of upstream support::rtl.styles (loaded via View::prependNamespace, no upstream edit).
    E1-01: removed the Google Fonts "Cairo" <link> and font rule (fonts are self-hosted by eunoia/theme, F14).
    Physical properties rewritten as logical ones with the same result under [dir="rtl"].
    The flex/margin double-flip rules are cleaned up in E1-06 (F15).
--}}
@if (in_array(app()->getLocale(), $rtlLocales))
    <style>
        [dir="rtl"] .fi-topbar { direction: rtl; }
        [dir="rtl"] .fi-sidebar { direction: rtl; }
        [dir="rtl"] .fi-main { direction: rtl; }
        [dir="rtl"] .fi-header { direction: rtl; }
        [dir="rtl"] .fi-simple-main { direction: rtl; }

        [dir="rtl"] .fi-fo-field-wrp { text-align: start; }
        [dir="rtl"] .fi-fo-field-wrp label { text-align: start; }
        [dir="rtl"] .fi-input-wrp { direction: rtl; }
        [dir="rtl"] input:not([type="email"]):not([type="url"]):not([type="tel"]),
        [dir="rtl"] textarea {
            text-align: start;
            direction: rtl;
        }
        [dir="rtl"] input[type="email"],
        [dir="rtl"] input[type="url"],
        [dir="rtl"] input[type="tel"] {
            direction: ltr;
            text-align: start;
        }

        [dir="rtl"] .fi-btn { flex-direction: row-reverse; }
        [dir="rtl"] .fi-btn > span + svg,
        [dir="rtl"] .fi-btn > svg + span { margin-inline-end: 0; margin-inline-start: 0.5rem; }

        [dir="rtl"] .fi-simple-page { direction: rtl; text-align: start; }
        [dir="rtl"] .fi-simple-header { text-align: center; }
        [dir="rtl"] .fi-simple-main form { direction: rtl; }

        [dir="rtl"] .fi-link { direction: rtl; }
        [dir="rtl"] .fi-ac { direction: rtl; }

        [dir="rtl"] .fi-dropdown-list { text-align: start; }

        [dir="rtl"] .fi-topbar-nav { direction: rtl; }
        [dir="rtl"] nav { direction: rtl; }

        [dir="rtl"] .fi-section { direction: rtl; }
        [dir="rtl"] .fi-section-header { text-align: start; }

        [dir="rtl"] .fi-ta { direction: rtl; }
        [dir="rtl"] .fi-ta-header-cell { text-align: start; }
        [dir="rtl"] .fi-ta-cell { text-align: start; }
    </style>
@endif
