<?php

class TemplateManager
{

    private $quoteContainer;

    public function __construct()
    {
        $this->quoteContainer = ['summary_html', 'summary'];
    }

    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);

        return $replaced;
    }

    private function getQuote($data)
    {
        return (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : null;
    }

    private function getUser($data)
    {
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();

        return (isset($data['user']) and ($data['user'] instanceof User)) ? $data['user'] : $APPLICATION_CONTEXT->getCurrentUser();
    }

    private function replaceTextsQuote($text, $quoteFromRepository)
    {
        $container = $this->quoteContainer;

        foreach ($container as $content) {
            $contained = strpos($text, '[quote:' . $content . ']');
            if ($contained !== false) {
                $text = str_replace(
                    '[quote:' . $content . ']',
                    Quote::renderHtml($quoteFromRepository),
                    $text
                );
            }
        }

        return $text;
    }

    private function replaceTextsDestination($text, $quote, $_quoteFromRepository)
    {
        $usefulObject = SiteRepository::getInstance()->getById($quote->siteId);
        $destinationOfQuote = DestinationRepository::getInstance()->getById($quote->destinationId);

        if(strpos($text, '[quote:destination_link]') !== false) {
            $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            $text = str_replace('[quote:destination_link]', $usefulObject->url . '/' . $destination->countryName . '/quote/' . $_quoteFromRepository->id, $text);
        }else {
            $text = str_replace('[quote:destination_link]', '', $text);
        }

        if(strpos($text, '[quote:destination_name]') !== false) {
            $text = str_replace('[quote:destination_name]', $destinationOfQuote->countryName, $text);
        }

        return $text;
    }

    private function replaceTexts($text, $_quoteFromRepository, $quote)
    {
        $text = $this->replaceTextsQuote($text, $_quoteFromRepository);
        $text = $this->replaceTextsDestination($text, $quote, $_quoteFromRepository);

        return $text;
    }

    private function computeQuote ($text, $quote)
    {
        if ($quote) {
            $_quoteFromRepository = QuoteRepository::getInstance()->getById($quote->id);
            $text = $this->replaceTexts($text, $_quoteFromRepository, $quote);
        }

        return $text;
    }

    private function computeUser($text, $_user)
    {
        if($_user) {
            (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($_user->firstname)), $text);
        }

        return $text;
    }

    private function computeText($text, array $data)
    {
        $quote = $this->getQuote($data);
        $text = $this->computeQuote($text, $quote);

        $_user  = $this->getUser($data);
        $text = $this->computeUser($text, $_user);

        return $text;
    }
}
