<?php

namespace LaravelOpenTracing;

use OpenTracing\SpanContext;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer;

final class LocalTracer implements Tracer
{
    /**
     * @var LocalScopeManager
     */
    private $scopeManager;

    /**
     * @var LocalSpan[]
     */
    private $spans;

    public function __construct()
    {
        $this->scopeManager = new LocalScopeManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveSpan()
    {
        $activeScope = $this->scopeManager->getActive();
        return $activeScope ? $activeScope->getSpan() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeManager()
    {
        return $this->scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function startSpan($operationName, $options = [])
    {
        $options = $this->asStartSpanOptions($options);

        $references = $options->getReferences();
        if (empty($references)) {
            $spanContext = LocalSpanContext::createAsRoot();
        } else {
            $spanContext = LocalSpanContext::createAsChildOf($references[0]->getContext());
        }

        $span = new LocalSpan($operationName, $spanContext);
        $this->spans[] = $span;
        return $span;
    }

    /**
     * {@inheritdoc}
     */
    public function startActiveSpan($operationName, $options = [])
    {
        $options = $this->asStartSpanOptions($options);
        if (($activeSpan = $this->getActiveSpan()) !== null) {
            $options = $options->withParent($activeSpan);
        }

        $span = $this->startSpan($operationName, $options);
        return $this->scopeManager->activate($span, $options->shouldFinishSpanOnClose());
    }

    /**
     * {@inheritdoc}
     */
    public function inject(SpanContext $spanContext, $format, &$carrier)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function extract($format, $carrier)
    {
        return LocalSpanContext::create();
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->spans = [];
    }

    /**
     * @param array|StartSpanOptions $options
     * @return StartSpanOptions
     */
    private function asStartSpanOptions($options)
    {
        return $options instanceof StartSpanOptions ? $options : StartSpanOptions::create($options);
    }
}