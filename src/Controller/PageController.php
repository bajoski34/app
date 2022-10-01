<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class PageController extends AbstractController
{
    private NewsRepository $newsRepository;

    public function __construct(NewsRepository $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }

    /**
    * @Route("/news", name="news")
    */
    public function index(Request $request, SessionInterface $session): Response
    {
        $page = $request->query->get('page', 1); //page one by default
        $limit = $request->query->get('limit', 10); //10 items per page by default

        $newsQueryBuilder = $this->newsRepository->createNewsOrderedByDateDescQueryBuilder();
        $pagerfanta = new Pagerfanta(new QueryAdapter($newsQueryBuilder));
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        $totalPage = $pagerfanta->getNbPages();
        $session->set('currentPage', $page);
        $session->set('totalPage', $totalPage);

        if($page > $totalPage) {
            throw $this->createNotFoundException('Page not found');
        }

        if($pagerfanta->getNbResults() === 0) {
            $this->addFlash('warning', 'No news found');
        }

        return $this->render('news/index.html.twig', [
            'news' => $pagerfanta ,
        ]);
    }

    /**
     * @Route("/news/delete", name="delete_news", methods={"POST"})
     */
    public function delete(Request $request, SessionInterface $session): Response
    {
        $totalArticles = $request->query->get('articles', 1);
        $limit = $request->query->get('limit', 10);

        $page = $session->get('currentPage', 1);
        $articlesLeft = $totalArticles % $limit;;

        if($articlesLeft == 1){
            $page = $page - 1;
        }

        $id = $request->request->get('id');
        $news = $this->newsRepository->find($id);

        if ($news) {
            $this->newsRepository->remove($news, true);
        }

        $this->addFlash('success', 'News deleted successfully');

        return $this->redirectToRoute('news', ['page' => $page]);
    }
}