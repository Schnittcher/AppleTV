<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class LocalizationTest extends TestCase
{
    public function testEveryTextHasAGermanTranslation(): void
    {
        $german = $this->germanTranslations();

        $missing = array_values(array_filter(
            array_merge($this->formTexts(), $this->translateKeys()),
            fn (string $text) => !isset($german[$text])
        ));

        $this->assertSame([], $missing, 'Texte ohne deutsche Übersetzung in locale.json');
    }

    public function testTranslationsAreNotEmptyAndHaveNoUnusedKeys(): void
    {
        $german = $this->germanTranslations();
        $used = array_flip(array_merge($this->formTexts(), $this->translateKeys()));

        foreach ($german as $key => $translation) {
            $this->assertNotSame('', trim($translation), 'Leere Übersetzung für "' . $key . '"');
            $this->assertArrayHasKey($key, $used, 'Übersetzung für "' . $key . '" wird nirgends verwendet');
        }
    }

    public function testSourceTextsAreEnglish(): void
    {
        // Die Originaltexte sind englisch: kein Umlaut und kein "ß" in Beschriftungen und Translate-Schlüsseln
        foreach (array_merge($this->formTexts(), $this->translateKeys()) as $text) {
            $this->assertDoesNotMatchRegularExpression('/[äöüÄÖÜß]/u', $text, 'Deutsches Original: ' . $text);
        }
    }

    private function germanTranslations(): array
    {
        $locale = json_decode((string) file_get_contents(__DIR__ . '/../AppleTV/locale.json'), true);
        return $locale['translations']['de'];
    }

    // Alle Schlüssel, die module.php an Translate() übergibt
    private function translateKeys(): array
    {
        $code = (string) file_get_contents(__DIR__ . '/../AppleTV/module.php');
        preg_match_all("/Translate\\('((?:[^'\\\\]|\\\\.)*)'\\)/", $code, $matches);
        return array_values(array_unique($matches[1]));
    }

    // Alle Beschriftungen der form.json (caption), auch die des Spendenblocks
    private function formTexts(): array
    {
        $form = json_decode((string) file_get_contents(__DIR__ . '/../AppleTV/form.json'), true);
        $texts = [];
        array_walk_recursive($form, function ($value, $key) use (&$texts)
        {
            if ($key === 'caption' && is_string($value) && $value !== '') {
                $texts[] = $value;
            }
        });
        return array_values(array_unique($texts));
    }
}
