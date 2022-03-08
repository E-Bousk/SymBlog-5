<?php

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigCustomFunctionsExtension extends AbstractExtension
{
    private TranslatorInterface $translator;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator)
    {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'readinThisLanguageButton',
                [$this, 'displayReadInThisLanguageButton'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function displayReadInThisLanguageButton(string $locale): string
    {
        if ($locale === 'fr') {
            $readInEnglishLabel = $this->translator->trans(
                'articles.read.english_language',
                [],
                'articles_content'
            );

            return "<a href=\"{$this->getUpdateLocaleUrl($locale)}\" class=\"my-3 btn btn-primary\">{$readInEnglishLabel}</a>";
        } else {
            $readInFrenchLabel = $this->translator->trans(
                'articles.read.french_language',
                [],
                'articles_content'
            );

            return "<a href=\"{$this->getUpdateLocaleUrl($locale)}\" class=\"my-3 btn btn-primary\">{$readInFrenchLabel}</a>";
        }
    }

    private function getUpdateLocaleUrl(string $locale): string
    {
        $locale = $locale === 'fr' ? 'en' : 'fr';

        return $this->urlGenerator->generate('app_locale_update', compact('locale'));
    }
}
