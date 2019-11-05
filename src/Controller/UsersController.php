<?php

namespace App\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractFOSRestController
{
    public $token = '123456789';
    public function optionsUsersAction(Request $request) {
        return \FOS\RestBundle\View\View::create([
        ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

    public function postUserAction(Request $request) {
        try {
            $user = new \App\Entity\User;
            $user
                ->setUsername($request->get('username'))
                ->setEmail($request->get('email'))
                ->setPassword($request->get('password'))
                ->setPseudo($request->get('pseudo'))
                ->setCreatedAt(new \Datetime())
            ;
            $em = $this->get('doctrine')->getEntityManager();
            $em->persist($user);
            $em->flush();
            return \FOS\RestBundle\View\View::create([
                'message' => 'OK',
                'data' => $user
            ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
        } catch (\Exception $e) {
            return \FOS\RestBundle\View\View::create([
                'message' => 'bad request',
                'data' => $e->getMessage()
            ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);            
        }
    }

    public function optionsAuthsAction(Request $request) {
        return \FOS\RestBundle\View\View::create([
        ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

    public function postAuthAction(Request $request) {
        $em = $this->get('doctrine')->getEntityManager();
        $user = $em->getRepository(\App\Entity\User::class)->findOneByUsername($request->get('username'));
        if ($user) {
            return \FOS\RestBundle\View\View::create([
                'message' => 'OK',
                'data' => [
                    'token' => $this->token, 
                    'user' => $user
                ]
            ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
        }
        return \FOS\RestBundle\View\View::create([
            'message' => 'bad request',
            'data' => 'Not connect'
        ], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);            

    }

    public function optionsUsersVideosAction($user, Request $request) {
        return \FOS\RestBundle\View\View::create([
        ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }


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
}