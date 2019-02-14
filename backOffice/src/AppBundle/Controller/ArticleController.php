<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Article;
use AppBundle\Entity\User;
use AppBundle\Form\ArticleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ArticleController extends Controller
{
    /**
     * @Route("/listArticle", name="listArticle")
     */
    public function listArticleAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $article = $this->getDoctrine()
            ->getRepository(Article::class)
            ->findAll();

        return $this->render('backOffice/listArticle.html.twig', [
            'articles' => $article
        ]); 
    }

    /**
     * @Route("/publication/{id}", name="publication", requirements={"id":"\d+"})
     */
    public function togglePublicationAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $articleToToggle = $this->getDoctrine()
            ->getRepository(Article::class)
            ->findOneById($id);

        if ($articleToToggle->getPublish() == false)
        {
            $articleToToggle->setPublish(true);
        }
        else
        {
            $articleToToggle->setPublish(false);
        }
        $em->flush();

        $article = $this->getDoctrine()
            ->getRepository(Article::class)
            ->findAll();

        return $this->render('backOffice/listArticle.html.twig', [
            'articles' => $article
        ]);    
    }

    /**
     * @Route("/modification/{id}", name="modification")
     */
    public function modifyArticleAction(Request $request, Article $article, $id)
    {
        $articleContents = $this->getDoctrine()
            ->getRepository(Article::class)
            ->find($id);
        $test = $articleContents->getImage();
        $articleContents->setImage(null);
        $form = $this->createForm(ArticleType::class, $article);
        
        $form->handleRequest($request);
        
        $em = $this->getDoctrine()->getManager();

        
        if ($form->isSubmitted() && $form->isValid())
        {
            
            $article = $form->getData();
            
            $file = $article->getImage();
            if ($file != null)
            {
                $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();

            // Move the image to the image directory
                try {
                    $file->move(
                        $this->getParameter('image_directory'),
                        $fileName
                    );
                    } catch (FileException $e) {

                }
                $article->setImage($fileName);
            }
            else
            {
                $article->setImage($test);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article mis à jour');

            return $this->redirectToRoute('listArticle');
        }
        return $this->render('backOffice/editArticle.html.twig', [
            'formArticle' => $form->createView(),
            'test' => $test
        ]);
        
    }

    /**
     * @Route("/addArticle", name="addArticle")
     */
    public function addArticleAction(Request $request, AuthenticationUtils $authenticationUtils)
    {
        // Create a new article and process the form
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $em = $this->getDoctrine()->getManager();

            $authorName = $this->getUser()->getId();
            $author = $this->getDoctrine()
                ->getRepository(User::class)
                ->findOneById($authorName);
            //$author = $this->getUser()->getId();

            $article->setAuthor($author);
            
            $file = $article->getImage();
            if ($file != null)
            {
                $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();

            // Move the image to the image directory
                try {
                    $file->move(
                        $this->getParameter('image_directory'),
                        $fileName
                    );
                    } catch (FileException $e) {

                }
                $article->setImage($fileName);
            }            
            $article->setDateArticle(new \Datetime());

            $em->persist($article);

            $em->flush();
            return $this->redirectToRoute('listArticle');
            }

        return $this->render('backOffice/addArticle.html.twig', [
            'formArticle' => $form->createView(),
        ]);
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }


}