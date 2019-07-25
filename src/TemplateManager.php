<?php

class TemplateManager
{

    private function getQuote($data)
    {
        return (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : null;
    }

    private function getUser($data)
    {
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();

        return (isset($data['user'])  and ($data['user']  instanceof User))  ? $data['user']  : $APPLICATION_CONTEXT->getCurrentUser();
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


    private function replaceTextsQuote($text, $quoteFromRepository)
    {
        $text = $this->quoteToHTML($text, $quoteFromRepository);
        $text = $this->quoteToText($text, $quoteFromRepository);
        return $text;
    }

    private function quoteToHtml($text, $quoteFromRepository)
    {
        if(strpos($text, '[quote:summary_html]')){
            return str_replace(
                '[quote:summary_html]',
                Quote::renderHtml($quoteFromRepository),
                $text
            );
        }
        return $text;

    }

    private function quoteToText($text, $quoteFromRepository)
    {
        if (strpos($text, '[quote:summary]')) {
            return str_replace(
                '[quote:summary]',
                Quote::renderText($quoteFromRepository),
                $text
            );
        }
        return $text;
    }


    private function replaceDestinationLink($text, $quote, $_quoteFromRepository)
    {
        if(strpos($text, '[quote:destination_link]')) {
            $usefulObject = SiteRepository::getInstance()->getById($quote->siteId);
            $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            $text = str_replace('[quote:destination_link]', $usefulObject->url . '/' . $destination->countryName . '/quote/' . $_quoteFromRepository->id, $text);
        }else {
            $text = str_replace('[quote:destination_link]', '', $text);
        }

        return $text;
    }

    private function replaceDestinationName($text, $destinationOfQuote)
    {
        if(strpos($text, '[quote:destination_name]')) {
            $text = str_replace('[quote:destination_name]', $destinationOfQuote->countryName, $text);
        }

        return $text;
    }

    private function replaceTextsDestination($text, $quote, $_quoteFromRepository)
    {
        $destinationOfQuote = DestinationRepository::getInstance()->getById($quote->destinationId);

        $text = $this->replaceDestinationLink($text, $quote, $_quoteFromRepository);
        $text = $this->replaceDestinationName($text, $destinationOfQuote);

        return $text;
    }

    private function replaceTexts($text, $_quoteFromRepository, $quote)
    {
        $text = $this->replaceTextsQuote($text, $_quoteFromRepository);
        $text = $this->replaceTextsDestination($text, $quote, $_quoteFromRepository);

        return $text;
    }

    private function computeQuote($text, $quote)
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
            (strpos($text, '[user:first_name]')) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($_user->firstname)), $text);
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
