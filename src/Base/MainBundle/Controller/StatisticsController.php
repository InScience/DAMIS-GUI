<?php

namespace Base\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Web statistics for administrators. The awstats container (docker/awstats) builds static AWStats pages every
 * hour into %damis_awstats_dir%/<lang>/<YYYY-MM>/ (Lithuanian and English) plus icon/; they are shown in the
 * admin layout. Only AWStats page and icon file names inside that directory are served.
 */
#[Route('/admin/statistics')]
#[IsGranted('ROLE_ADMIN')]
class StatisticsController extends AbstractController
{
    public function __construct(
        #[Autowire('%damis_awstats_dir%')]
        private readonly string $awstatsDir,
    ) {
    }

    /** Latest month in the language of the interface. */
    #[Route('', name: 'statistics', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $lang = str_starts_with($request->getLocale(), 'en') ? 'en' : 'lt';
        $months = $this->months($lang);
        if (!$months) {
            return $this->render('@BaseMain/Statistics/index.html.twig', ['lang' => $lang, 'months' => [], 'month' => null]);
        }

        return $this->redirectToRoute('statistics_month', ['lang' => $lang, 'month' => $months[0]]);
    }

    #[Route('/{lang}/{month}', name: 'statistics_month', requirements: ['lang' => 'lt|en', 'month' => '\d{4}-\d{2}'], methods: ['GET'])]
    public function month(string $lang, string $month): Response
    {
        $months = $this->months($lang);
        if (!in_array($month, $months, true)) {
            throw $this->createNotFoundException('No statistics for this month');
        }

        return $this->render('@BaseMain/Statistics/index.html.twig', ['lang' => $lang, 'months' => $months, 'month' => $month]);
    }

    /** A static AWStats page (the main page and its detail pages link to each other relatively). */
    #[Route('/{lang}/{month}/{file}', name: 'statistics_file', requirements: ['lang' => 'lt|en', 'month' => '\d{4}-\d{2}', 'file' => 'awstats\.damis(\.[a-z0-9]+)?\.html'], methods: ['GET'])]
    public function page(string $lang, string $month, string $file): Response
    {
        $response = $this->serve($lang.'/'.$month.'/'.$file);
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        // rebuilt every hour
        $response->headers->set('Cache-Control', 'private, no-cache');

        return $response;
    }

    /** Icons referenced by the pages (AWStats DirIcons = /admin/statistics/icon). */
    #[Route('/icon/{path}', name: 'statistics_icon', requirements: ['path' => '[A-Za-z0-9_\-]+(/[A-Za-z0-9_.\-]+)*\.(png|gif|jpg|ico)'], methods: ['GET'])]
    public function icon(string $path): Response
    {
        $response = $this->serve('icon/'.$path);
        $response->setPrivate();
        $response->setMaxAge(86400);

        return $response;
    }

    private function serve(string $relativePath): BinaryFileResponse
    {
        $base = '' !== $this->awstatsDir ? realpath($this->awstatsDir) : false;
        $path = $base ? realpath($base.'/'.$relativePath) : false;
        // realpath + prefix check: nothing outside the statistics directory, even through symlinks
        if (!$path || !str_starts_with($path, $base.DIRECTORY_SEPARATOR) || !is_file($path)) {
            throw $this->createNotFoundException('Statistics file not found');
        }

        return new BinaryFileResponse($path);
    }

    /**
     * Months that have pages in the given language, newest first.
     *
     * @return list<string>
     */
    private function months(string $lang): array
    {
        $dir = $this->awstatsDir.'/'.$lang;
        if ('' === $this->awstatsDir || !is_dir($dir)) {
            return [];
        }

        $months = array_values(array_filter(
            scandir($dir) ?: [],
            static fn (string $name): bool => 1 === preg_match('/^\d{4}-\d{2}$/', $name) && is_file($dir.'/'.$name.'/awstats.damis.html'),
        ));
        rsort($months);

        return $months;
    }
}
