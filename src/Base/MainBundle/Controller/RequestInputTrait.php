<?php

namespace Base\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Replacement for Request::get() (deprecated in Symfony 7.4, removed in 8.0) for the actions whose
 * parameters arrive either in the query string or in the POST body, depending on the JavaScript that calls
 * them (Routing.generate() puts them in the URL, the dialogs post them). Same lookup order as
 * Request::get() without the route attributes, which none of these parameters are.
 */
trait RequestInputTrait
{
    private function input(Request $request, string $key, mixed $default = null): mixed
    {
        if ($request->query->has($key)) {
            return $request->query->all()[$key];
        }
        if ($request->request->has($key)) {
            return $request->request->all()[$key];
        }

        return $default;
    }
}
