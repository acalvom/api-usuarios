<?php


namespace App\Controller;


use App\Entity\Message;
use App\Entity\Result;
use App\Entity\User;
use App\Utility\Utils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsController::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsController extends AbstractController

{
    public const RUTA_API = '/api/v1/results';

    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';
    private const ROLE_ADMIN = 'ROLE_ADMIN';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * CGET Action
     * Summary: Retrieves the collection of Result resources.
     * Notes: Returns all results from the system that the user has access to.
     *
     * @param   Request $request
     * @return  Response
     * @Route(
     *     path=".{_format}/{sort?id}",
     *     defaults={ "_format": "json", "sort": "id" },
     *     requirements={
     *         "sort": "id|$resultEnt",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="cget"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function cgetAction(Request $request): Response
    {
        $results = $this->entityManager
            ->getRepository(Result::class)
            ->findAll();
        $format = Utils::getFormat($request);

        // No hay resultados?
        if (empty($results)) {
            return $this->error404($format);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ 'results' => array_map(fn ($r) =>  ['$resultEnt' => $r], $results) ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'must-revalidate',
                self::HEADER_ETAG => md5(json_encode($results)),
            ]
        );
    }

    /**
     * GET Action
     * Summary: Retrieves a Result resource based on a single ID.
     * Notes: Returns the result identified by &#x60;resultId&#x60;.
     *
     * @param Request $request
     * @param  int $resultId Result id
     * @return Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *          "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="get"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */

    public function getAction(Request $request, int $resultId): Response
    {
        $resultEnt = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);
        $format = Utils::getFormat($request);

        if (empty($resultEnt)) {
            return $this->error404($format);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ Result::RESULT_ENT_ATTR => $resultEnt ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'must-revalidate',
                self::HEADER_ETAG => md5(json_encode($resultEnt)),
            ]
        );
    }

    /**
     * Summary: Provides the list of HTTP supported methods
     * Notes: Return a &#x60;Allow&#x60; header with a list of HTTP supported methods.
     *
     * @param  int $resultId Result id
     * @return Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "resultId" = 0, "_format": "json" },
     *     requirements={
     *          "$resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_OPTIONS },
     *     name="options"
     * )
     */

    public function optionsAction(int $resultId): Response
    {
        $methods = $resultId
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [ Request::METHOD_GET, Request::METHOD_POST ];
        $methods[] = Request::METHOD_OPTIONS;

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(', ', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
    }

    /**
     * DELETE Action
     * Summary: Removes the result resource.
     * Notes: Deletes the result identified by &#x60;resultId&#x60;.
     *
     * @param   Request $request
     * @param   int $resultId Result id
     * @return  Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_DELETE },
     *     name="delete"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */

    public function deleteAction(Request $request, int $resultId): Response
    {
        // Puede crear un usuario sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            throw new HttpException(   // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access'
            );
        }
        $format = Utils::getFormat($request);

        $resultEnt = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (null === $resultEnt) {   // 404 - Not Found
            return $this->error404($format);
        }

        $this->entityManager->remove($resultEnt);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * POST action
     * Summary: Creates a Result resource.
     *
     * @param Request $request request
     * @return Response
     * @Route(
     *     path=".{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_POST },
     *     name="post"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function postAction(Request $request): Response
    {
        // Puede crear un resultado sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            throw new HttpException(   // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access'
            );
        }
        $body = $request->getContent();
        $postData = json_decode($body, true);
        $format = Utils::getFormat($request);

        if (!isset($postData[Result::RESULT_ATTR], $postData[User::EMAIL_ATTR])) {
            // 422 - Unprocessable Entity -> Faltan datos
            $message = new Message(Response::HTTP_UNPROCESSABLE_ENTITY, Response::$statusTexts[422]);
            return Utils::apiResponse(
                $message->getCode(),
                $message,
                $format
            );
        }

        // hay datos -> procesarlos
        $user_exist = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([ User::EMAIL_ATTR => $postData[User::EMAIL_ATTR] ]);

        if ($user_exist === null) {    // 400 - Bad Request
            $message = new Message(Response::HTTP_BAD_REQUEST, Response::$statusTexts[400]);
            return Utils::apiResponse(
                $message->getCode(),
                $message,
                $format
            );
        }

        // time
        (isset($postData[Result::TIME_ATTR])) ? $time = $postData[Result::TIME_ATTR]
            : $time = new DateTime('now');

        // 201 - Created
        $resultEnt = new Result(
            $postData[Result::RESULT_ATTR],
            $user_exist,
            $time
        );

        $this->entityManager->persist($resultEnt);
        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ Result::RESULT_ENT_ATTR => $resultEnt ],
            $format,
            [
                'Location' => self::RUTA_API . '/' . $resultEnt->getId(),
            ]
        );
    }

    /**
     * PUT action
     * Summary: Updates the Result resource.
     * Notes: Updates the result identified by &#x60;resultId&#x60;.
     *
     * @param Request $request request

     * @param int $resultId Result id
     * @return  Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_PUT },
     *     name="put"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function putAction(Request $request, int $resultId): Response
    {
        // Puede editar otro resultado diferente sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            throw new HttpException(   // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access'
            );
        }
        $body = $request->getContent();
        $postData = json_decode($body, true);
        $format = Utils::getFormat($request);

        $resultEnt = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (null === $resultEnt) {    // 404 - Not Found
            return $this->error404($format);
        }

        if (isset($postData[Result::ID_ATTR],$postData[Result::RESULT_ATTR])) {
            $result_exist = $this->entityManager
                ->getRepository(Result::class)
                ->findOneBy([ Result::ID_ATTR => $postData[Result::ID_ATTR] ]);

            if (null === $result_exist) {    // 400 - Bad Request
                $message = new Message(Response::HTTP_BAD_REQUEST, Response::$statusTexts[400]);
                return Utils::apiResponse(
                    $message->getCode(),
                    $message,
                    $format
                );
            }
            $resultEnt->setResult($postData[Result::RESULT_ATTR]);
            $resultEnt->setTime(new DateTime('now'));
        }

        $this->entityManager->flush();

        return Utils::apiResponse(
            209,                        // 209 - Content Returned
            [ Result::RESULT_ENT_ATTR => $resultEnt ],
            $format
        );
    }

    /**
     * Response 404 Not Found
     * @param string $format
     *
     * @return Response
     */
    private function error404(string $format): Response
    {
        $message = new Message(Response::HTTP_NOT_FOUND, Response::$statusTexts[404]);
        return Utils::apiResponse(
            $message->getCode(),
            $message,
            $format
        );
    }


}