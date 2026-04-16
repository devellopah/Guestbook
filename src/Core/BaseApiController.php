<?php

namespace Core;

use Core\Request;
use Core\Response;
use Services\MessageService;
use Services\UserService;

abstract class BaseApiController extends BaseController
{
  protected Request $request;
  protected Response $response;

  public function __construct(MessageService $messageService, UserService $userService, Request $request, Response $response)
  {
    parent::__construct($messageService, $userService);
    $this->request = $request;
    $this->response = $response;
  }

  protected function getJsonBody(): array
  {
    $contentType = $this->request->getHeader('Content-Type');
    if (strpos($contentType, 'application/json') === false) {
      return [];
    }

    $body = $this->request->getBody();
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return [];
    }

    return $data;
  }

  protected function wantsJson(): bool
  {
    $acceptHeader = $this->request->getHeader('Accept');
    return strpos($acceptHeader, 'application/json') !== false;
  }

  protected function isAjax(): bool
  {
    return $this->request->isAjax();
  }
}
