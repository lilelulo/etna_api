<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Entity\Video;
use App\Form\UserType;
use App\Form\UserUpdateType;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class UsersController extends AbstractFOSRestController
{
    public function postUserAction(Request $request) {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'csrf_protection' => false,
            'method' => 'POST'
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setCreatedAt(new \DateTime());
            try {
                $this->getDoctrine()->getManager()->persist($user);
                $this->getDoctrine()->getManager()->flush();
            } catch (\Exception $e) {
                return View::create([
                    'message' => 'Bad Request',
                    'code' => "00002",
                    'data' => $e
                ],  Response::HTTP_BAD_REQUEST);
            }
            return View::create([
                'message' => 'OK',
                'data' => $user
            ], Response::HTTP_CREATED);
        }
        return View::create([
            'message' => 'Bad Request',
            'code' => "00001",
            'data' => $form
        ],  Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Security("cuser == user")
     */
    public function putUserAction(Request $request, User $cuser) {
        $form = $this->createForm(UserUpdateType::class, $cuser, [
            'csrf_protection' => false,
            'method' => 'PUT'
        ]);
        $form->submit($request->request->all(), false);
        if ($form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->persist($cuser);
                $this->getDoctrine()->getManager()->flush();
            } catch (\Exception $e) {
                return View::create([
                    'message' => 'Bad Request',
                    'code' => "00002",
                    'data' => $e
                ],  Response::HTTP_BAD_REQUEST);
            }
            return View::create([
                'message' => 'OK',
                'data' => $cuser
            ], Response::HTTP_OK);
        }
        return View::create([
            'message' => 'Bad Request',
            'code' => "00001",
            'data' => $form
        ],  Response::HTTP_BAD_REQUEST);
    }

    public function getUserAction(Request $request, User $cuser) {
        $view = View::create([
            'message' => 'OK',
            'data' => $cuser
        ], Response::HTTP_OK);
        if (!$this->getUser() || $this->getUser() != $cuser) {
            $view->setContext((new \FOS\RestBundle\Context\Context())->setGroups(['Default'])->setSerializeNull(true));
        }
        return $view;
    }

    public function getUsersAction(Request $request) {
        if ($request->get('page') <= 0 || $request->get('perPage') <= 0) {
            return View::create([
                'message' => 'Bad Request',
                'code' => "00004",
                'data' => []
            ],  Response::HTTP_BAD_REQUEST);
        }
        $premierResultat = ($request->get('page') - 1) * $request->get('perPage');
        $donnees = $this->getDoctrine()->getRepository(User::class)
            ->createQueryBuilder('e')
            ->setFirstResult($premierResultat)
            ->setMaxResults($request->get('perPage'));

        $paginator = new Paginator($donnees);

        $view = View::create([
            'message' => 'Ok',
            'data' => $paginator->getIterator()->getArrayCopy(),
            'pager' => [
                'total' => $paginator->count(),
                'current' => (int) $request->get('page')
            ]
        ],  Response::HTTP_OK);
        $view->setContext((new \FOS\RestBundle\Context\Context())->setGroups(['Default'])->setSerializeNull(true));
        return $view;
    }

    public function postAuthAction(Request $request) {
        try {
            $em = $this->get('doctrine')->getEntityManager();
            $user = $em->getRepository(\App\Entity\User::class)->findOneByUsername($request->get('username'));
            if ($user->getPassword() != $request->get('password')) {
                throw new \Exception('password');
            }
            $token = (new Token())->setCode(uniqid())->setUser($user)->setExpiredAt(new \DateTime('+1month'));
            $this->getDoctrine()->getManager()->persist($token);
            $this->getDoctrine()->getManager()->flush();
            return \FOS\RestBundle\View\View::create([
                'message' => 'OK',
                'data' => $token
            ], \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return \FOS\RestBundle\View\View::create([
                'message' => 'bad request',
                'code' => '00004',
                'data' => $e
            ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        } catch (\Error $e) {
            return \FOS\RestBundle\View\View::create([
                'message' => 'bad request',
                'code' => '00005',
                'data' => $e
            ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        }
    }


    /**
     * @Security("cuser == user")
     */
    public function deleteUserAction(Request $request, User $cuser) {
        try {
            $this->getDoctrine()->getManager()->remove($cuser);
            $this->getDoctrine()->getManager()->flush();
            return View::create([],  Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return View::create([
                'message' => 'Bad Request',
                'code' => "00002",
                'data' => $e
            ],  Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Security("cuser == user")
     */
    public function postUserVideoAction(Request $request, User $cuser) {
        try {
            $em = $this->get('doctrine')->getEntityManager();
            $video = new Video();

            $path = $this->getParameter('kernel.project_dir') .
                '/public/uploads/'.
                basename($_FILES['source']['tmp_name']);
            copy(
                $_FILES['source']['tmp_name'],
                $path
            );
            $video
                ->setName($request->get('name'))
                ->setDuration(15)
                ->setUser($cuser)
                ->setCreatedAt(new \Datetime())
                ->setSource($path)
            ;
            $em->persist($video);
            $em->flush();
            return \FOS\RestBundle\View\View::create([
                'message' => 'OK',
                'data' => $video
            ], \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return \FOS\RestBundle\View\View::create([
                'message' => 'bad request',
                'data' => $e->getMessage()
            ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);            
        }
    }

    public function getVideosAction(Request $request) {
        $premierResultat = ($request->get('page') - 1) * $request->get('perPage');
        $donnees = $this->getDoctrine()->getRepository(Video::class)
            ->createQueryBuilder('e')
            ->setFirstResult($premierResultat)
            ->setMaxResults($request->get('perPage'));

        $paginator = new Paginator($donnees);

        $view = View::create([
            'message' => 'Ok',
            'data' => $paginator->getIterator()->getArrayCopy(),
            'pager' => [
                'total' => $paginator->count(),
                'current' => (int) $request->get('page')
            ]
        ],  Response::HTTP_OK);
        $view->setContext((new \FOS\RestBundle\Context\Context())->setGroups(['Default'])->setSerializeNull(true));
        return $view;
    }

    public function getUserVideosAction(Request $request, User $cuser) {
        $premierResultat = ($request->get('page') - 1) * $request->get('perPage');
        $donnees = $this->getDoctrine()->getRepository(Video::class)
            ->createQueryBuilder('e')
            ->andWhere('e.user = :user')
                ->setParameter('user', $cuser)
            ->setFirstResult($premierResultat)
            ->setMaxResults($request->get('perPage'));

        $paginator = new Paginator($donnees);

        $view = View::create([
            'message' => 'Ok',
            'data' => $paginator->getIterator()->getArrayCopy(),
            'pager' => [
                'total' => $paginator->count(),
                'current' => (int) $request->get('page')
            ]
        ],  Response::HTTP_OK);
        $view->setContext((new \FOS\RestBundle\Context\Context())->setGroups(['Default'])->setSerializeNull(true));
        return $view;
    }

    public function putVideoAction(Request $request, Video $video) {
        try {
            $em = $this->get('doctrine')->getEntityManager();
            $video->setName($request->get('name'));
            $em->persist($video);
            $em->flush();
            return \FOS\RestBundle\View\View::create([
                'message' => 'OK',
                'data' => $video
            ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
        } catch (\Exception $e) {
            return \FOS\RestBundle\View\View::create([
                'message' => 'bad request',
                'data' => $e->getMessage()
            ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        }
    }

    public function postVideoCommentAction(Request $request, Video $video) {
        return \FOS\RestBundle\View\View::create([
            'message' => 'OK',
            'data' => [
                'id' => rand(1,100),
                'user' => $this->getUser(),
                'body' => $request->get('body')
            ]
        ], \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
    }

    public function deleteVideoAction(Request $request, Video $video) {
        try {
            $em = $this->get('doctrine')->getEntityManager();
            $em->remove($video);
            $em->flush();
            return \FOS\RestBundle\View\View::create([
            ], \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return \FOS\RestBundle\View\View::create([
                'message' => 'bad request',
                'data' => $e->getMessage()
            ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        }
    }

    public function patchVideoAction(Video $video, Request $request) {
        try {
            $em = $this->get('doctrine')->getEntityManager();
            $format = $video->getFormat();
            $format[$request->get('format')] = $request->get('source');
            $video->setFormat($format);
            $em->persist($video);
            $em->flush();
            return \FOS\RestBundle\View\View::create([
                'message' => 'OK',
                'data' => $video
            ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
        } catch (\Exception $e) {
            return \FOS\RestBundle\View\View::create([
                'message' => 'bad request',
                'data' => $e->getMessage()
            ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);            
        }
    }


}