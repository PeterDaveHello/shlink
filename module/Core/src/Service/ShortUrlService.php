<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Laminas\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Service\ShortUrl\FindShortCodeTrait;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;

class ShortUrlService implements ShortUrlServiceInterface
{
    use FindShortCodeTrait;
    use TagManagerTrait;

    private ORM\EntityManagerInterface $em;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params): Paginator
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $paginator = new Paginator(new ShortUrlRepositoryAdapter($repo, $params));
        $paginator->setItemCountPerPage(ShortUrlRepositoryAdapter::ITEMS_PER_PAGE)
                  ->setCurrentPageNumber($params->page());

        return $paginator;
    }

    /**
     * @param string[] $tags
     * @throws ShortUrlNotFoundException
     */
    public function setTagsByShortCode(string $shortCode, array $tags = []): ShortUrl
    {
        $shortUrl = $this->findByShortCode($this->em, new ShortUrlIdentifier($shortCode));
        $shortUrl->setTags($this->tagNamesToEntities($this->em, $tags));
        $this->em->flush();

        return $shortUrl;
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function updateMetadataByShortCode(string $shortCode, ShortUrlMeta $shortUrlMeta): ShortUrl
    {
        $shortUrl = $this->findByShortCode($this->em, new ShortUrlIdentifier($shortCode));
        $shortUrl->updateMeta($shortUrlMeta);

        $this->em->flush();

        return $shortUrl;
    }
}
