<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Article;
use AppBundle\Exception\ResourceValidationException;
use AppBundle\Form\ArticleType;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationList;


/**
 * Class ArticleController
 * @package AppBundle\Controller
 */
class ArticleController extends FOSRestController
{


    /**
     * @Rest\Get(
     *     path="/articles",
     *     name="article_list"
     * )
     * @Rest\QueryParam(
     *     name="keyword",
     *     requirements="[a-zA-Z0-9]",
     *     nullable=true,
     *     description="The keyword to search for"
     * )
     * @Rest\QueryParam(
     *     name="order",
     *     requirements="asc|desc",
     *     default="asc",
     *     description="Sort order (asc or desc)"
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     default="15",
     *     description="Max number of movies per page."
     * )
     * @Rest\QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     default="1",
     *     description="The pagination offset"
     * )
     * @Rest\View()
     *
     * @ApiDoc(
     *     section="Articles",
     *     resource=true,
     *     description="Get the list of all articles."
     * )
     */
    public function listAction(ParamFetcherInterface $paramFetcher){
        $em = $this->getDoctrine()->getManager();
        $list = $em->getRepository(Article::class)->search(
            $paramFetcher->get('keyword'),
            $paramFetcher->get('order'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('offset')
        );

        return new \AppBundle\Representation\Article($list);

    }

    /**
     * @Rest\Get(
     *     path="/articles/{id}",
     *     name="article_detail",
     *     requirements={
     *          "id" = "\d+"
     *      }
     * )
     * @Rest\View()
     *
     * @ApiDoc(
     *     section="Articles",
     *     resource=true,
     *     description="Get one article.",
     *     requirements={
     *          {
     *              "name"="id",
     *              "dataType"="integer",
     *              "requirement"="\d+",
     *              "description"="The article unique identifier."
     *          }
     *     }
     * )
     */
    public function detailAction(Article $article){
        return $article;
    }

    /**
     * @Rest\Post(
     *     path="/articles",
     *     name="article_create"
     * )
     * @Rest\View(statusCode= 201)
     * @ParamConverter(
     *     "article",
     *     converter="fos_rest.request_body",
     *     options={
     *      "validator"={"groups" = "Create"}
     *     }
     * )
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Articles",
     *     description="Create an article",
     *     input={"class" = Article::class, "title"=""},
     *     statusCodes={
     *          201 = "Returned when created",
     *          400 = "Returned when a violation is raised by validation"
     * }
     * )
     */
    public function createAction(Article $article, ConstraintViolationList $violations)
    {
        // Vérification en cas d'erreur
        self::constraintViolationCheck($violations);

        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();

        // On régénère en fournissant le lien d'accès à la ressource crée
        return $this->view($article, Response::HTTP_CREATED, [
           'Location' => $this->generateUrl('article_detail',['id' => $article->getId(), UrlGeneratorInterface::ABSOLUTE_URL])
        ]);
    }

    /**
     * @Rest\Put(
     *     path="/articles/{id}",
     *     name="article_update",
     *     requirements = {
     *      "id" = "\d+"
     *      }
     * )
     * @Rest\View()
     */
    public function editAction(Article $article, Request $request){
        $em = $this->getDoctrine()->getManager();
        // Construction du formulaire autour de l'objet
        $form = $this->createForm(ArticleType::class, $article);
        // Soummission du formulaire
        $form->submit($request->request->all());
        // Application de la validation des données saisies
        $errors = $this->get('validator')->validate($article, null, ['Create']);
        // Vérification en cas d'erreur
        self::constraintViolationCheck($errors);
        // Enregistrement
        $em->flush();


        return $this->view($article, Response::HTTP_ACCEPTED, [
            'Location' => $this->generateUrl('article_detail',['id' => $article->getId()])
        ]);
    }

    /**
     * @Rest\Patch(
     *     path="/articles/{id}",
     *     name="article_partiel_update",
     *     requirements = {
     *      "id" = "\d+"
     *      }
     * )
     * @Rest\View()
     */
    public function editPatchAction(Article $article, Request $request){
        $em = $this->getDoctrine()->getManager();
        // Construction du formulaire autour de l'objet
        $form = $this->createForm(ArticleType::class, $article);
        // Soummission du formulaire
        $form->submit($request->request->all(),false);
        // Application de la validation des données saisies
        $errors = $this->get('validator')->validate($article, null, ['Create']);
        // Verification en cas d'erreur
        self::constraintViolationCheck($errors);
        // Enregistrement
        $em->flush();


        return $this->view($article, Response::HTTP_ACCEPTED, [
            'Location' => $this->generateUrl('article_detail',['id' => $article->getId()])
        ]);
    }

    /**
     * @Rest\Delete(
     *     path="/articles/{id}",
     *     name="article_delete",
     *     requirements={
     *          "id" = "\d+"
     *      }
     * )
     * @Rest\View()
     */
    public function deleteAction(Article $article){

        $em = $this->getDoctrine()->getManager();
        $em->remove($article);
        $em->flush();

        return $this->view(['context' => 'Opération effectuée avec succès'], Response::HTTP_OK);
    }


    /**
     * @param array $constraint : Représente le nombre de violation de contrainte
     * @throws ResourceValidationException
     */
    private static function constraintViolationCheck($constraint){
        $message = "The JSON sent contains invalid data. Here are the errors you need to correct: ";

        if (count($constraint)){

            foreach ($constraint as $violation){
                $message  .= sprintf("Field %s:%s", $violation->getPropertyPath(),$violation->getMessage());
            }

            throw new ResourceValidationException($message);
        }
    }


}
