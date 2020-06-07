<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Form\UserType;
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

/*
    public function postUserVideoAction($user, Request $request) {
        try {
            $em = $this->get('doctrine')->getEntityManager();
            $video = new \App\Entity\Video;

            copy($_FILES['source']['tmp_name'], $this->getParameter('kernel.project_dir') . '/public/uploads/'.basename($_FILES['source']['tmp_name']));
            $video
                ->setName($request->get('name'))
                ->setDuration(15)
                ->setUser($em->getRepository(\App\Entity\User::class)->find($user))
                ->setCreatedAt(new \Datetime())
                ->setSource($this->getParameter('kernel.project_dir') . '/public/uploads/'.basename($_FILES['source']['tmp_name']))
            ;
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



    public function patchVideoAction($id, Request $request) {
        try {
            $em = $this->get('doctrine')->getEntityManager();
            $video = $em->getRepository(\App\Entity\Video::class)->find($id);
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

*/
}