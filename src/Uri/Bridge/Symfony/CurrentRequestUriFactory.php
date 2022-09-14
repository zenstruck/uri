<?php

namespace Zenstruck\Uri\Bridge\Symfony;

use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class CurrentRequestUriFactory
{
    private RequestStack $requests;

    public function __construct(RequestStack $requests)
    {
        $this->requests = $requests;
    }

    public function create(): Uri
    {
        if (!$request = $this->requests->getCurrentRequest()) {
            throw new \LogicException('No request available.');
        }

        return Uri::new($request);
    }
}
