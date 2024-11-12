<?php

namespace Nodiskindrivea\PatchworksApi;

use Amp\Http\Client\Response;

class HttpException extends \Amp\Http\Client\HttpException
{
    public readonly Response $response;
    public function __construct(string $message = "", int $code = 0, ?Response $response = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }
}
