<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Endroid\QrCode\QrCode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;

class QrCodeAction implements MiddlewareInterface
{
    private const DEFAULT_SIZE = 300;
    private const MIN_SIZE = 50;
    private const MAX_SIZE = 1000;

    private ShortUrlResolverInterface $urlResolver;
    private array $domainConfig;
    private LoggerInterface $logger;

    public function __construct(
        ShortUrlResolverInterface $urlResolver,
        array $domainConfig,
        ?LoggerInterface $logger = null
    ) {
        $this->urlResolver = $urlResolver;
        $this->domainConfig = $domainConfig;
        $this->logger = $logger ?: new NullLogger();
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $identifier = ShortUrlIdentifier::fromRedirectRequest($request);

        try {
            $shortUrl = $this->urlResolver->resolveEnabledShortUrl($identifier);
        } catch (ShortUrlNotFoundException $e) {
            $this->logger->warning('An error occurred while creating QR code. {e}', ['e' => $e]);
            return $handler->handle($request);
        }

        $qrCode = new QrCode($shortUrl->toString($this->domainConfig));
        $qrCode->setSize($this->getSizeParam($request));
        $qrCode->setMargin(0);

        return new QrCodeResponse($qrCode);
    }

    private function getSizeParam(Request $request): int
    {
        $size = (int) $request->getAttribute('size', self::DEFAULT_SIZE);
        if ($size < self::MIN_SIZE) {
            return self::MIN_SIZE;
        }

        return $size > self::MAX_SIZE ? self::MAX_SIZE : $size;
    }
}
