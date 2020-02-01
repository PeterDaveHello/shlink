<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Response\PixelResponse;
use Shlinkio\Shlink\Core\Action\PixelAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Service\VisitsTracker;

class PixelActionTest extends TestCase
{
    private PixelAction $action;
    private ObjectProphecy $urlResolver;
    private ObjectProphecy $visitTracker;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->visitTracker = $this->prophesize(VisitsTracker::class);

        $this->action = new PixelAction(
            $this->urlResolver->reveal(),
            $this->visitTracker->reveal(),
            new AppOptions(),
        );
    }

    /** @test */
    public function imageIsReturned(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))->willReturn(
            new ShortUrl('http://domain.com/foo/bar'),
        )->shouldBeCalledOnce();
        $this->visitTracker->track(Argument::cetera())->shouldBeCalledOnce();

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        $this->assertInstanceOf(PixelResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('image/gif', $response->getHeaderLine('content-type'));
    }
}
