<?php
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo\Formatter;

use FeedIo\Feed\NodeInterface;
use FeedIo\FeedInterface;
use FeedIo\Rule\OptionalField;
use FeedIo\RuleSet;
use FeedIo\Standard\XmlAbstract;
use FeedIo\FormatterInterface;

/**
 * Turns a FeedInterface instance into a XML document.
 *
 * Depends on :
 *  - FeedIo\StandardAbstract
 *
 */
class XmlFormatter implements FormatterInterface
{

    /**
     * @var XmlAbstract
     */
    protected $standard;

    /**
     * @param XmlAbstract $standard
     */
    public function __construct(XmlAbstract $standard)
    {
        $this->standard = $standard;
    }

    /**
     * @param  \DOMDocument  $document
     * @param  FeedInterface $feed
     * @return $this
     */
    public function setHeaders(\DOMDocument $document, FeedInterface $feed)
    {
        $rules = $this->standard->getFeedRuleSet();
        $mainElement = $this->standard->getMainElement($document);
        $this->buildElements($rules, $document, $mainElement, $feed);

        return $this;
    }

    /**
     * @param  \DOMDocument  $document
     * @param  NodeInterface $node
     * @return $this
     */
    public function addItem(\DOMDocument $document, NodeInterface $node)
    {
        $domItem = $document->createElement($this->standard->getItemNodeName());
        $rules = $this->standard->getItemRuleSet();
        $this->buildElements($rules, $document, $domItem, $node);

        $this->standard->getMainElement($document)->appendChild($domItem);

        return $this;
    }

    /**
     * @param RuleSet $ruleSet
     * @param \DOMDocument $document
     * @param \DOMElement $rootElement
     * @param NodeInterface $node
     */
    public function buildElements(RuleSet $ruleSet, \DOMDocument $document, \DOMElement $rootElement, NodeInterface $node) : void
    {
        $rules = $this->getAllRules($ruleSet, $node);

        foreach ($rules as $rule) {
            $rule->apply($document, $rootElement, $node);
        }
    }

    /**
     * @param  RuleSet              $ruleSet
     * @param  NodeInterface        $node
     * @return array|\ArrayIterator
     */
    public function getAllRules(RuleSet $ruleSet, NodeInterface $node)
    {
        $rules = $ruleSet->getRules();
        $optionalFields = $node->listElements();
        foreach ($optionalFields as $optionalField) {
            $rules[] = new OptionalField($optionalField);
        }

        return $rules;
    }

    /**
     * @return \DOMDocument
     */
    public function getEmptyDocument()
    {
        return new \DOMDocument('1.0', 'utf-8');
    }

    /**
     * @return \DOMDocument
     */
    public function getDocument()
    {
        $document = $this->getEmptyDocument();

        return $this->standard->format($document);
    }

    /**
     * @param  FeedInterface $feed
     * @return string
     */
    public function toString(FeedInterface $feed)
    {
        $document = $this->toDom($feed);

        return $document->saveXML();
    }

    /**
     * @param  FeedInterface $feed
     * @return \DomDocument
     */
    public function toDom(FeedInterface $feed)
    {
        $document = $this->getDocument();

        $this->setHeaders($document, $feed);
        $this->setItems($document, $feed);

        return $document;
    }

    /**
     * @param  \DOMDocument  $document
     * @param  FeedInterface $feed
     * @return $this
     */
    public function setItems(\DOMDocument $document, FeedInterface $feed)
    {
        foreach ($feed as $item) {
            $this->addItem($document, $item);
        }

        return $this;
    }
}
