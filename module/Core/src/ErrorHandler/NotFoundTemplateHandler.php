<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Laminas\Diactoros\Response;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotFoundTemplateHandler implements RequestHandlerInterface
{
    public const NOT_FOUND_TEMPLATE = 'ShlinkCore::error/404';
    public const INVALID_SHORT_CODE_TEMPLATE = 'ShlinkCore::invalid-short-code';

    private TemplateRendererInterface $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Dispatch the next available middleware and return the response.
     *
     *
     * @throws InvalidArgumentException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class, RouteResult::fromRouteFailure(null));
        $status = StatusCodeInterface::STATUS_NOT_FOUND;

        $template = $routeResult->isFailure() ? self::NOT_FOUND_TEMPLATE : self::INVALID_SHORT_CODE_TEMPLATE;
        return new Response\HtmlResponse($this->renderer->render($template), $status);
    }
}
