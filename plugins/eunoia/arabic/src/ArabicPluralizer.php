<?php

namespace Eunoia\Arabic;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\Inflector\WordInflector;
use Illuminate\Support\Pluralizer;

/**
 * Teaches Str::plural() Arabic: glossary plurals for Arabic labels, and never an English "s"
 * on an Arabic word ("فاتورةs" → "فواتير", finding F10). English words keep the English rules.
 * Note: Pluralizer::useLanguage() resets the inflector; call ArabicPluralizer::install() again afterwards.
 */
class ArabicPluralizer implements WordInflector
{
    /**
     * @param  array<string, string>  $plurals  Arabic singular => plural
     */
    public function __construct(
        protected WordInflector $english,
        protected array $plurals,
    ) {}

    public function inflect(string $word): string
    {
        if (! preg_match('/\p{Arabic}/u', $word)) {
            return $this->english->inflect($word);
        }

        return $this->plurals[$word] ?? $word;
    }

    /**
     * @param  array<string, string>  $plurals
     */
    public static function install(array $plurals): void
    {
        $english = InflectorFactory::createForLanguage('english')->build();

        $singularizer = new class($english) implements WordInflector
        {
            public function __construct(protected Inflector $english) {}

            public function inflect(string $word): string
            {
                return preg_match('/\p{Arabic}/u', $word) ? $word : $this->english->singularize($word);
            }
        };

        $pluralizer = new class($english) implements WordInflector
        {
            public function __construct(protected Inflector $english) {}

            public function inflect(string $word): string
            {
                return $this->english->pluralize($word);
            }
        };

        $inflector = new Inflector($singularizer, new self($pluralizer, $plurals));

        (function () use ($inflector): void {
            static::$inflector = $inflector;
        })->bindTo(null, Pluralizer::class)();
    }
}
